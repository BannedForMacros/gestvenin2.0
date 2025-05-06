<?php

namespace App\Http\Controllers;

use App\Models\ProductoVenta;
use App\Models\ProductoAlmacen;
use App\Models\InsumosPorProducto;
use Illuminate\Http\Request;

class InsumosPorProductoController extends Controller
{
    public function index()
    {
        $productosVentas = ProductoVenta::with('insumos.productoAlmacen')->get();
        return view('insumos.index', compact('productosVentas'));
    }
    

    // Mostrar el formulario para asignar insumos a un producto de venta
    public function create($productoVentaId)
    {
        // Buscar el producto de venta
        $productoVenta = ProductoVenta::findOrFail($productoVentaId);

        // Obtener todos los productos de almacén (insumos disponibles)
        $productosAlmacen = ProductoAlmacen::where('activo', true)->get();

        // Pasar los datos a la vista
        return view('insumos.create', compact('productoVenta', 'productosAlmacen'));
    }

    // Guardar los insumos asociados a un producto de venta
    public function store(Request $request, $productoVentaId)
    {
        // Validar los datos
        $validated = $request->validate([
            'insumos.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
            'insumos.*.cantidad' => 'required|numeric|min:0',
        ]);

        // Eliminar los insumos existentes para este producto de venta
        InsumosPorProducto::where('producto_venta_id', $productoVentaId)->delete();

        // Guardar los nuevos insumos asociados
        foreach ($validated['insumos'] as $insumo) {
            InsumosPorProducto::create([
                'producto_venta_id' => $productoVentaId,
                'producto_almacen_id' => $insumo['producto_almacen_id'],
                'cantidad' => $insumo['cantidad'],
            ]);
        }

        // Redirigir con un mensaje de éxito
        return redirect()->route('productos_ventas.index')->with('success', 'Insumos asignados correctamente.');
    }

    // Editar los insumos asociados a un producto de venta
    public function edit($productoVentaId)
    {
        // Buscar el producto de venta
        $productoVenta = ProductoVenta::findOrFail($productoVentaId);

        // Obtener todos los productos de almacén (insumos disponibles)
        $productosAlmacen = ProductoAlmacen::where('activo', true)->get();

        // Obtener los insumos asociados actualmente a este producto de venta
        $insumosActuales = InsumosPorProducto::where('producto_venta_id', $productoVentaId)->get();

        // Pasar los datos a la vista
        return view('insumos.edit', compact('productoVenta', 'productosAlmacen', 'insumosActuales'));
    }

    // Actualizar los insumos asociados a un producto de venta
    public function update(Request $request, $productoVentaId)
    {
        // Validar los datos
        $validated = $request->validate([
            'insumos.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
            'insumos.*.cantidad' => 'nullable|numeric|min:0',
            'insumos.*.activo' => 'nullable|boolean',
        ]);
    
        // Eliminar los insumos existentes para este producto de venta
        InsumosPorProducto::where('producto_venta_id', $productoVentaId)->delete();
    
        // Guardar solo los insumos seleccionados y con cantidad válida
        foreach ($validated['insumos'] as $insumo) {
            if (isset($insumo['activo']) && $insumo['activo'] == 1 && isset($insumo['cantidad']) && $insumo['cantidad'] > 0) {
                InsumosPorProducto::create([
                    'producto_venta_id' => $productoVentaId,
                    'producto_almacen_id' => $insumo['producto_almacen_id'],
                    'cantidad' => $insumo['cantidad'],
                ]);
            }
        }
    
        // Redirigir con un mensaje de éxito
        return redirect()->route('insumos.index')->with('success', 'Insumos actualizados correctamente.');
    }
    
    
    
    
    
}
