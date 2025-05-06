<?php

namespace App\Http\Controllers;

use App\Models\ClasificacionGasto;
use Illuminate\Http\Request;

class ClasificacionGastoController extends Controller
{
    public function index()
    {
        $clasificacionesGastos = ClasificacionGasto::with('tipoGasto')->get(); // Usar plural
        return view('clasificaciones_gastos.index', compact('clasificacionesGastos')); // Pasar plural
    }
    

    public function create()
    {
        $tiposGastos = \App\Models\TipoGasto::all(); // Obtener todos los tipos de gasto
        return view('clasificaciones_gastos.create', compact('tiposGastos'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_gasto_id' => 'required|exists:tipos_gastos,id',
        ]);
        ClasificacionGasto::create($request->all());
        return redirect()->route('clasificaciones_gastos.index')->with('success', 'Clasificación creada con éxito.');
    }

    public function edit($id)
    {
        $clasificacionGasto = ClasificacionGasto::findOrFail($id);
        $tiposGastos = \App\Models\TipoGasto::all(); // Obtener todos los tipos de gasto
        return view('clasificaciones_gastos.edit', compact('clasificacionGasto', 'tiposGastos'));
    }
    

    public function update(Request $request, $id)
    {
        $clasificacionGasto = ClasificacionGasto::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_gasto_id' => 'required|exists:tipos_gastos,id',
        ]);
        $clasificacionGasto->update($request->all());
        return redirect()->route('clasificaciones_gastos.index')->with('success', 'Clasificación actualizada con éxito.');
    }

    public function destroy($id)
    {
        // Obtener la clasificación
        $clasificacion = ClasificacionGasto::findOrFail($id);
    
        // Cambiar el estado (activo o inactivo)
        $clasificacion->activo = !$clasificacion->activo;
        $clasificacion->save();
    
        // Preparar mensaje
        $mensaje = $clasificacion->activo ? 'Clasificación activada con éxito.' : 'Clasificación inactivada con éxito.';
    
        // Retornar JSON para AJAX
        return response()->json([
            'success' => true,
            'mensaje' => $mensaje,
            'nuevo_estado' => $clasificacion->activo
        ]);
    }
    
}
