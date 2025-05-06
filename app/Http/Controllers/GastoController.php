<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\TipoGasto;
use App\Models\ClasificacionGasto;
use App\Models\Local;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $query = Gasto::with(['tipoGasto', 'clasificacionGasto', 'local'])
            ->where('activo', 1)
            ->orderBy('fecha_gasto', 'desc')
            ->orderBy('created_at', 'desc');
    
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_gasto', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_gasto', '<=', $request->fecha_fin);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo_gasto_id', $request->tipo);
        }
        if ($request->filled('clasificacion')) {
            $query->where('clasificacion_gasto_id', $request->clasificacion);
        }
    
        if ($request->ajax()) {
            return response()->json($query->get());
        }
    
        return view('gastos.index', [
            'gastos' => $query->get(),
            'tipos' => TipoGasto::all(),
            'clasificaciones' => ClasificacionGasto::all()
        ]);
    }

    public function create()
    {
        $locales = Local::all(); 
        $tiposGastos = TipoGasto::all(); 
        $clasificacionesGastos = ClasificacionGasto::where('activo', 1)->get(); 
    
        return view('gastos.create', compact('locales', 'tiposGastos', 'clasificacionesGastos'));
    }

    /**
     * Almacenar uno o varios gastos manuales en una sola acción.
     * - Si vienen campos sueltos (descripcion, monto, etc.), se convierte a un array con un solo gasto.
     * - Si viene un array (gastos[0], gastos[1], etc.), se procesan varios.
     */
    public function store(Request $request)
    {
        // 1. Detectar si se envió un array de gastos
        if (!$request->has('gastos')) {
            // Significa que es un gasto individual.
            // Validar como gasto único:
            $validatedSingle = $request->validate([
                'descripcion' => 'nullable|string',
                'monto' => 'required|numeric|min:0',
                'tipo_gasto_id' => 'required|exists:tipos_gastos,id',
                'clasificacion_gasto_id' => 'required|exists:clasificaciones_gastos,id',
                'local_id' => 'nullable|exists:locales,id',
                'fecha_gasto' => 'required|date',
                'comprobante_de_pago' => 'nullable|string|max:255',
            ]);

            // Forzar origen manual y sin origen_id
            $validatedSingle['origen'] = 'manual';
            $validatedSingle['origen_id'] = null;

            // Convertirlo a un array de 1 elemento
            $request->merge([
                'gastos' => [$validatedSingle]
            ]);
        }

        // 2. Ahora validamos como si siempre viniera 'gastos'
        $data = $request->validate([
            'gastos' => 'required|array|min:1',
            'gastos.*.descripcion' => 'nullable|string',
            'gastos.*.monto' => 'required|numeric|min:0',
            'gastos.*.tipo_gasto_id' => 'required|exists:tipos_gastos,id',
            'gastos.*.clasificacion_gasto_id' => 'required|exists:clasificaciones_gastos,id',
            'gastos.*.local_id' => 'nullable|exists:locales,id',
            'gastos.*.fecha_gasto' => 'required|date',
            'gastos.*.comprobante_de_pago' => 'nullable|string|max:255',
            // 'gastos.*.origen' => 'sometimes|string', // si deseas validarlo
            // 'gastos.*.origen_id' => 'sometimes|integer|nullable', // si deseas validarlo
        ]);

        // 3. Crear cada gasto
        foreach ($data['gastos'] as $gastoData) {
            // Si no se forzó antes, forzamos aquí:
            $gastoData['origen'] = 'manual';
            $gastoData['origen_id'] = null;

            Gasto::create($gastoData);
        }

        // 4. Retornar respuesta
        // Podrías hacer redirect en vez de JSON, según tu preferencia:
        return redirect()->route('gastos.index')
                         ->with('success', 'Gasto(s) creado(s) con éxito.');
    }

    public function edit($id)
    {
        $gasto = Gasto::findOrFail($id); 
        $locales = Local::all(); 
        $tiposGastos = TipoGasto::all(); 
        $clasificacionesGastos = ClasificacionGasto::all(); 
    
        return view('gastos.edit', compact('gasto', 'locales', 'tiposGastos', 'clasificacionesGastos'));
    }

    public function update(Request $request, $id)
    {
        $gasto = Gasto::findOrFail($id);

        $request->validate([
            'descripcion' => 'nullable|string',
            'monto' => 'required|numeric|min:0',
            'tipo_gasto_id' => 'required|exists:tipos_gastos,id',
            'clasificacion_gasto_id' => 'required|exists:clasificaciones_gastos,id',
            'local_id' => 'nullable|exists:locales,id',
            'fecha_gasto' => 'required|date',
            'comprobante_de_pago' => 'nullable|string|max:255',
        ]);

        // Forzar a seguir siendo "manual", si así lo deseas:
        // $data = $request->all();
        // $data['origen'] = 'manual';
        // $data['origen_id'] = null;
        // $gasto->update($data);

        // O permitir que se actualicen sólo los campos enviados:
        $gasto->update($request->all());

        return redirect()->route('gastos.index')->with('success', 'Gasto actualizado con éxito.');
    }

    public function destroy($id)
    {
        $gasto = Gasto::findOrFail($id);
        $gasto->update(['activo' => 0]); // Cambiar el estado a inactivo
    
        return response()->json([
            'success' => true,
            'message' => 'Gasto marcado como inactivo con éxito.'
        ]);
    }

    public function getClasificaciones($tipoId)
    {
        $clasificaciones = ClasificacionGasto::where('tipo_gasto_id', $tipoId)
            ->where('activo', 1)
            ->get();
    
        return response()->json($clasificaciones);
    }
}
