<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
{
    public function index()
    {
        $unidadesMedida = UnidadMedida::all();
        return view('unidades_medida.index', compact('unidadesMedida'));
    }

    public function create()
    {
        return view('unidades_medida.create');
    }

    public function store(Request $request)
    {
        // Validación de los campos 'nombre' y 'codigo'
        $request->validate([
            'nombre' => 'required|unique:unidades_medida',
            'codigo' => 'required|unique:unidades_medida|alpha', // Validación del código
        ]);

        // Crear la unidad de medida con los datos validados
        UnidadMedida::create($request->all());
        return redirect()->route('unidades_medida.index')->with('success', 'Unidad de medida creada con éxito.');
    }

    public function edit($id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);
        return view('unidades_medida.edit', compact('unidadMedida'));
    }
    
    
    public function update(Request $request, $id)
    {
        // Buscar la unidad de medida por su ID
        $unidadMedida = UnidadMedida::findOrFail($id);
    
        // Validación de los campos
        $request->validate([
            'nombre' => 'required|unique:unidades_medida,nombre,' . $unidadMedida->id,
            'codigo' => 'required|unique:unidades_medida,codigo,' . $unidadMedida->id,
        ]);
    
        // Actualizar la unidad de medida con los datos validados
        $unidadMedida->update($request->all());
    
        // Redireccionar con un mensaje de éxito
        return redirect()->route('unidades_medida.index')->with('success', 'Unidad de medida actualizada con éxito.');
    }
    
    

    public function destroy($id)
    {
        // Recuperar el modelo
        $unidadMedida = UnidadMedida::findOrFail($id);
    
        // Alternar el estado (1 -> 0 o 0 -> 1)
        $unidadMedida->activo = !$unidadMedida->activo;
        $unidadMedida->save();
    
        // Redirigir con un mensaje de éxito
        return redirect()->route('unidades_medida.index')
            ->with('success', 'El estado de la unidad de medida se actualizó correctamente.');
    }
    
    
}
