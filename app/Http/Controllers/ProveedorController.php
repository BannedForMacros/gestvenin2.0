<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::where('activo', true)->get();
        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ]);

        Proveedor::create($request->all());

        return redirect()->route('proveedores.index')->with('success', 'Proveedor creado con éxito.');
    }

    public function edit($id) // Acepta el ID como parámetro
    {
        $proveedor = Proveedor::findOrFail($id); // Buscar el proveedor por ID
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, $id) // Acepta el ID como parámetro
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ]);

        $proveedor = Proveedor::findOrFail($id); // Buscar el proveedor por ID
        $proveedor->update($request->all());

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado con éxito.');
    }

    public function destroy($id)
    {
        // Buscar el proveedor por ID y desactivarlo
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update(['activo' => false]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor desactivado con éxito.');
    }
}
