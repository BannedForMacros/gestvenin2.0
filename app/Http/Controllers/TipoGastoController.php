<?php

namespace App\Http\Controllers;

use App\Models\TipoGasto;
use Illuminate\Http\Request;

class TipoGastoController extends Controller
{
    public function index()
    {
        $tiposGastos = TipoGasto::all(); // Recuperar todos los tipos de gastos
        return view('tipos_gastos.index', compact('tiposGastos')); // Pasar los datos a la vista
    }

    public function create()
    {
        return view('tipos_gastos.create');
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255', 'descripcion' => 'nullable|string']);
        TipoGasto::create($request->all());
        return redirect()->route('tipos_gastos.index')->with('success', 'Tipo de gasto creado con éxito.');
    }

    public function edit($id)
    {
        $tipoGasto = TipoGasto::findOrFail($id);
        return view('tipos_gastos.edit', compact('tipoGasto'));
    }

    public function update(Request $request, $id)
    {
        $tipoGasto = TipoGasto::findOrFail($id);
        $request->validate(['nombre' => 'required|string|max:255', 'descripcion' => 'nullable|string']);
        $tipoGasto->update($request->all());
        return redirect()->route('tipos_gastos.index')->with('success', 'Tipo de gasto actualizado con éxito.');
    }

    public function destroy($id)
    {
        TipoGasto::destroy($id);
        return redirect()->route('tipos_gastos.index')->with('success', 'Tipo de gasto eliminado con éxito.');
    }
}
