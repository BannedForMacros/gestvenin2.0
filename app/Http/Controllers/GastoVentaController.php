<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\GastoVenta;
use App\Models\Gasto;
use App\Models\Local;
use App\Models\TipoGasto;
use App\Models\ClasificacionGasto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GastoVentaController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $fechaActual = Carbon::now()->format('Y-m-d');

        $query = GastoVenta::with(['local', 'tipoGasto', 'clasificacionGasto']);

        // Filtrar según el rol del usuario
        if ($user->hasRole('cajera')) {
            $query->where('local_id', $user->local_id);
        } elseif ($request->filled('local_id')) {
            $query->where('local_id', $request->input('local_id'));
        } else {
            $query->where('local_id', 1); // Default local
        }

        // Filtrar por fecha
        if ($request->filled('fecha_gasto')) {
            $query->whereDate('fecha_gasto', $request->input('fecha_gasto'));
        } else {
            $query->whereDate('fecha_gasto', $fechaActual);
        }

        $gastosVentas = $query->get();
        $locales = Local::all();
        $tiposGastos = TipoGasto::all();
        $clasificacionesGastos = ClasificacionGasto::all();

        return Inertia::render('GastosVentas/Index', [  // ← aquí nombras tu componente React
            'gastosVentas'          => $gastosVentas,
            'locales'               => $locales,
            'fechaActual'           => $fechaActual,
            'tiposGastos'           => $tiposGastos,
            'clasificacionesGastos' => $clasificacionesGastos,
        ]);
    }

    public function create()
    {
        // Vista para creación
        $locales = Local::all();
        $tiposGasto = TipoGasto::all();
        $clasificacionesGasto = ClasificacionGasto::all();
        $user = Auth::user();

        $localId = $user->hasRole('cajera') ? $user->local_id : null;

        return Inertia::render('GastosVentas/Create', [
            'locales'             => $locales,
            'tiposGasto'          => $tiposGasto,
            'clasificacionesGasto' => $clasificacionesGasto,
            'localId'             => $localId,
        ]);
    }

    /**
     * Al enviar varios gastos, cada uno creará:
     *  1) Una fila en 'gastos_ventas'.
     *  2) Una fila en 'gastos' con origen='gastos_ventas' y origen_id = $gastoVenta->id.
     * Se asume que la tabla 'gastos' sí tiene la columna 'origen_id'.
     */
    public function store(Request $request)
    {
        // Si el formulario envía un solo gasto sin 'gastos[]', lo convertimos
        if (!$request->has('gastos')) {
            $validatedSingle = $request->validate([
                'descripcion' => 'nullable|string|max:500',
                'monto' => 'required|numeric|min:0',
                'fecha_gasto' => 'required|date',
                'local_id' => 'required|exists:locales,id',
                'tipo_gasto_id' => 'required|exists:tipos_gastos,id',
                'clasificacion_gasto_id' => 'required|exists:clasificaciones_gastos,id',
                'comprobante_de_pago' => 'nullable|string|max:255',
            ]);

            $request->merge([
                'gastos' => [$validatedSingle]
            ]);
        }

        $data = $request->validate([
            'gastos' => 'required|array|min:1',
            'gastos.*.descripcion' => 'nullable|string|max:500',
            'gastos.*.monto' => 'required|numeric|min:0',
            'gastos.*.fecha_gasto' => 'required|date',
            'gastos.*.local_id' => 'required|exists:locales,id',
            'gastos.*.tipo_gasto_id' => 'required|exists:tipos_gastos,id',
            'gastos.*.clasificacion_gasto_id' => 'required|exists:clasificaciones_gastos,id',
            'gastos.*.comprobante_de_pago' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            foreach ($data['gastos'] as $gastoData) {
                // 1) Crear el gasto_venta
                $gastoVenta = GastoVenta::create($gastoData);
                if (!$gastoVenta) {
                    throw new \Exception("Error al crear gasto de venta.");
                }

                // 2) Crear el gasto relacionado
                $gasto = Gasto::create([
                    'local_id'               => $gastoData['local_id'],
                    'descripcion'            => $gastoData['descripcion'],
                    'monto'                  => $gastoData['monto'],
                    'fecha_gasto'            => $gastoData['fecha_gasto'],
                    'tipo_gasto_id'          => $gastoData['tipo_gasto_id'],
                    'clasificacion_gasto_id' => $gastoData['clasificacion_gasto_id'],
                    'origen'                 => 'gastos_ventas',
                    'origen_id'              => $gastoVenta->id, // referencia al ID de la fila en gastos_ventas
                    'comprobante_de_pago'    => $gastoData['comprobante_de_pago'] ?? null,
                    'activo'                 => 1,
                ]);
                if (!$gasto) {
                    throw new \Exception("Error al crear gasto en la tabla gastos.");
                }
            }
            DB::commit();

            // Si se está usando fetch (AJAX) que espera JSON, retornamos JSON:
            return response()->json([
                'success'  => true,
                'redirect' => route('gastos_ventas.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear gasto(s): ' . $e->getMessage(),
            ], 422);
        }
    }

    public function edit($id)
    {
        $gastoVenta = GastoVenta::findOrFail($id);
        $locales = Local::all();
        $tiposGastos = TipoGasto::all();
        $clasificacionesGastos = ClasificacionGasto::all();

        return Inertia::render('GastosVentas/EditModal', [
            'gastoVenta',
            'locales',
            'tiposGastos',
            'clasificacionesGastos'
        ]);
    }

    /**
     * Se busca la fila en 'gastos_ventas' y se actualiza;
     * luego, se busca la fila en 'gastos' usando (origen='gastos_ventas' AND origen_id=$id).
     */
    public function update(Request $request, $id)
    {
        $gastoVenta = GastoVenta::findOrFail($id);

        $validated = $request->validate([
            'descripcion'            => 'nullable|string|max:500',
            'monto'                  => 'required|numeric|min:0',
            'fecha_gasto'            => 'required|date',
            'local_id'               => 'required|exists:locales,id',
            'tipo_gasto_id'          => 'required|exists:tipos_gastos,id',
            'clasificacion_gasto_id' => 'required|exists:clasificaciones_gastos,id',
            'comprobante_de_pago'    => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // 1) Actualizar la fila en 'gastos_ventas'
            if (!$gastoVenta->update($validated)) {
                throw new \Exception("Error al actualizar el gasto de venta.");
            }

            // 2) Buscar y actualizar la fila en 'gastos'
            $gasto = Gasto::where('origen', 'gastos_ventas')
                          ->where('origen_id', $gastoVenta->id)
                          ->first();
            if (!$gasto) {
                throw new \Exception("No se encontró el gasto relacionado en la tabla gastos.");
            }
            if (!$gasto->update($validated)) {
                throw new \Exception("Error al actualizar el gasto en la tabla gastos.");
            }

            DB::commit();
            return redirect()->route('gastos_ventas.index')
                             ->with('success', 'Gasto de venta actualizado con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('gastos_ventas.index')
                             ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Se elimina la fila en 'gastos_ventas' y la fila relacionada en 'gastos'.
     * Se busca con (origen='gastos_ventas' AND origen_id=$id).
     */
    public function destroy($id)
    {
        $gastoVenta = GastoVenta::findOrFail($id);

        DB::beginTransaction();
        try {
            $gasto = Gasto::where('origen', 'gastos_ventas')
                          ->where('origen_id', $gastoVenta->id)
                          ->first();

            if ($gasto) {
                if (!$gasto->delete()) {
                    throw new \Exception("Error al eliminar el registro en la tabla gastos.");
                }
            }
            if (!$gastoVenta->delete()) {
                throw new \Exception("Error al eliminar el registro en la tabla gastos_ventas.");
            }
            DB::commit();
            return redirect()->route('gastos_ventas.index')
                             ->with('success', 'Gasto eliminado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('gastos_ventas.index')
                             ->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    /**
     * Cambia el estado 'activo' del gasto_venta y del gasto relacionado
     * (origen='gastos_ventas' AND origen_id=$id).
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $gastoVenta = GastoVenta::findOrFail($id);
            $nuevoEstado = !$gastoVenta->activo;
            $gastoVenta->activo = $nuevoEstado;
            if (!$gastoVenta->save()) {
                throw new \Exception("No se pudo actualizar el estado en 'gastos_ventas'.");
            }

            // Buscar y actualizar la tabla 'gastos'
            $gasto = Gasto::where('origen', 'gastos_ventas')
                          ->where('origen_id', $gastoVenta->id)
                          ->first();

            if (!$gasto) {
                throw new \Exception("No se encontró el gasto relacionado en la tabla gastos.");
            }

            $gasto->activo = $nuevoEstado;
            if (!$gasto->save()) {
                throw new \Exception("No se pudo actualizar el estado en la tabla 'gastos'.");
            }

            DB::commit();
            return redirect()->route('gastos_ventas.index')
                             ->with('success', 'Estado del gasto actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('gastos_ventas.index')
                             ->with('error', 'Error al actualizar estado: ' . $e->getMessage());
        }
    }
}
