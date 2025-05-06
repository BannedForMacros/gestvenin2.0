<?php

namespace App\Http\Controllers;

use App\Models\ProductoVenta;
use Illuminate\Http\Request;

class ProductoVentaController extends Controller
{
    // Mostrar la lista de productos
    public function index()
    {
        $productos = ProductoVenta::all(); // Puedes filtrar productos activos aquí si es necesario
        return view('productos_ventas.index', compact('productos'));
    }

    // Mostrar el formulario para crear un nuevo producto
    public function create()
    {
        return view('productos_ventas.create');
    }

    // Guardar un nuevo producto
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'precio' => 'required|numeric',
        ]);

        ProductoVenta::create($request->all());

        return redirect()->route('productos_ventas.index')->with('success', 'Producto creado exitosamente.');
    }
    // Mostrar el formulario para editar un producto
    public function edit($id)
    {
        $producto = ProductoVenta::findOrFail($id);
        return response()->json($producto);
    }
    

    // Actualizar un producto
    public function update(Request $request, $id)
    {
        try {
            $producto = ProductoVenta::findOrFail($id);
            $producto->update($request->all());
    
            return response()->json([
                'mensaje' => utf8_encode('Producto actualizado con éxito'),
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio' => $producto->precio,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo actualizar el producto',
                'mensaje' => $e->getMessage(),
            ], 500);
        }
    }
    
    // Eliminar o cambiar el estado del producto
    public function destroy(Request $request, ProductoVenta $productos_venta)
    {
        // Cambiar el estado del producto (activar si está inactivo, inactivar si está activo)
        $nuevoEstado = !$productos_venta->estado; // Invertir el estado
        $productos_venta->update(['estado' => $nuevoEstado]);
    
        // Preparar un mensaje de respuesta
        $mensaje = $nuevoEstado ? 'Producto activado.' : 'Producto inactivado.';
    
        // Retornar una respuesta JSON para AJAX
        return response()->json([
            'success' => true,
            'estado' => $nuevoEstado,
            'mensaje' => $mensaje
        ]);
    }
    
}

