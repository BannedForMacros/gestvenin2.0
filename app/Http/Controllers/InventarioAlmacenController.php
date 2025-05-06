<?php

namespace App\Http\Controllers;

use App\Models\DetalleHistorialInventario;
use App\Models\InventarioAlmacen;
use App\Models\ProductoAlmacen;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\HistorialInventario;
use Illuminate\Support\Facades\DB;

class InventarioAlmacenController extends Controller
{
    public function index()
    {
        // Obtener todo el inventario
        $inventario = InventarioAlmacen::with('productoAlmacen')->get();

        // Pasar el inventario a la vista
        return view('inventario_almacen.index', compact('inventario'));
    }

    // Método para redirigir a la vista de registrar una nueva entrada
    public function createEntrada()
    {
        $productos = ProductoAlmacen::all();
        return view('entradas_almacen.create', compact('productos'));
    }

    // Método para gestionar las entradas en el inventario (sin manejo de lotes)
    public function registrarEntrada(Request $request)
    {
        $request->validate([
            'productos.*.id'             => 'required|exists:productos_almacen,id',
            'productos.*.cantidad'       => 'required|integer|min:1',
            'productos.*.precio_unitario'=> 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->productos as $producto) {
                // Buscamos el registro único para este producto (ya que no se maneja por lotes)
                $registro = InventarioAlmacen::where('producto_almacen_id', $producto['id'])->first();

                if ($registro) {
                    // Actualizar el stock existente
                    $registro->cantidad += $producto['cantidad'];
                    // Actualizamos el precio unitario si es necesario
                    $registro->precio_unitario = $producto['precio_unitario'];
                    $registro->precio_total = $registro->cantidad * $registro->precio_unitario;
                    $registro->save();
                } else {
                    // Crear un nuevo registro en inventario
                    InventarioAlmacen::create([
                        'producto_almacen_id' => $producto['id'],
                        'cantidad'            => $producto['cantidad'],
                        'precio_unitario'     => $producto['precio_unitario'],
                        'precio_total'        => $producto['cantidad'] * $producto['precio_unitario'],
                        'fecha'               => now(),
                    ]);
                }
            }
        });

        return redirect()->route('inventario_almacen.index')->with('success', 'Entrada registrada correctamente.');
    }

    // Método para actualizar la cantidad mínima del inventario
    public function actualizarCantidadMinima(Request $request)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'producto_id'    => 'required|exists:inventario_almacen,id',
            'cantidad_minima'=> 'required|integer|min:0',
        ]);

        // Buscar el producto en el inventario
        $producto = InventarioAlmacen::findOrFail($validated['producto_id']);

        // Actualizar la cantidad mínima
        $producto->cantidad_minima = $validated['cantidad_minima'];
        $producto->save();

        return response()->json([
            'success' => true, 
            'message' => 'Cantidad mínima actualizada correctamente.'
        ]);
    }

    // Función para cerrar el inventario a las 23:59
    public function cerrarInventarioDiario()
    {
        // Obtener la hora actual
        $horaActual = Carbon::now()->format('H:i');

        // Verificar si la hora actual es 23:59
        if ($horaActual === '23:59') {
            // Guardar el inventario actual en la tabla historial_inventarios
            $this->guardarInventario();
            return response()->json(['message' => 'Inventario guardado a las 23:59']);
        } else {
            return response()->json(['message' => 'Aún no es 23:59. Inventario no guardado']);
        }
    }

    // Método para guardar el inventario en el historial (sin incluir lote)
    public function guardarInventario()
    {
        // Crear el historial de inventario principal
        $historial = HistorialInventario::create([
            'fecha'           => Carbon::now()->toDateString(),
            'total_productos' => InventarioAlmacen::count(),
        ]);

        // Obtener el inventario actual
        $inventarioActual = InventarioAlmacen::all();

        foreach ($inventarioActual as $producto) {
            // Calcular el dinero invertido para este producto
            $dineroInvertido = $producto->cantidad * $producto->precio_unitario;

            // Crear un detalle para el historial de inventario (sin campo lote)
            DetalleHistorialInventario::create([
                'historial_inventario_id' => $historial->id,
                'producto_almacen_id'     => $producto->producto_almacen_id,
                'cantidad'                => $producto->cantidad,
                'precio_unitario'         => $producto->precio_unitario,
                'dinero_invertido'        => $dineroInvertido,
            ]);
        }
    }

    // Método para obtener productos con stock bajo
    public function productosConStockBajo()
    {
        // Productos con stock total menor o igual a la cantidad mínima, pero mayor a 0
        $productosBajoStock = InventarioAlmacen::with('productoAlmacen')
            ->select('producto_almacen_id')
            ->selectRaw('SUM(cantidad) as cantidad_total')
            ->selectRaw('MAX(cantidad_minima) as cantidad_minima')
            ->groupBy('producto_almacen_id')
            ->havingRaw('cantidad_total > 0 AND cantidad_total <= cantidad_minima')
            ->get();
    
        return response()->json($productosBajoStock);
    }
    
    // Método para obtener productos sin stock
    public function productosSinStock()
    {
        // Productos con stock total igual a 0
        $productosSinStock = InventarioAlmacen::with('productoAlmacen')
            ->select('producto_almacen_id')
            ->selectRaw('SUM(cantidad) as cantidad_total')
            ->groupBy('producto_almacen_id')
            ->havingRaw('cantidad_total = 0')
            ->get();
    
        return response()->json($productosSinStock);
    }
    
    // Método para obtener el valor total del inventario
    public function valorTotalInventario()
    {
        $valorTotal = InventarioAlmacen::sum('dinero_invertido');
        return response()->json(['valor_total' => $valorTotal]);
    }

    // Método para actualizar el inventario (sin manejo de lotes)
    public function actualizarInventario(Request $request)
    {
        // Validar los datos del formulario
        $request->validate([
            'inventario'                   => 'required|array',
            'inventario.*.id'              => 'required|exists:inventario_almacen,id',
            'inventario.*.cantidad'        => 'required|numeric|min:0',
            'inventario.*.precio_unitario' => 'required|numeric|min:0',
        ]);
    
        try {
            DB::beginTransaction();
    
            foreach ($request->inventario as $item) {
                // Obtener el registro de inventario a actualizar
                $registro = InventarioAlmacen::findOrFail($item['id']);
    
                // Actualizar la cantidad y el precio
                $registro->cantidad        = $item['cantidad'];
                $registro->precio_unitario = $item['precio_unitario'];
                $registro->precio_total    = $registro->cantidad * $registro->precio_unitario;
                $registro->dinero_invertido= $registro->precio_total;
                $registro->save();
            }
    
            DB::commit();
            return redirect()->route('inventario_almacen.index')->with('success', 'Inventario actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar inventario: ' . $e->getMessage());
            return back()->with('error', 'Hubo un problema al actualizar el inventario: ' . $e->getMessage());
        }
    }
    public function stockProducto($id)
    {
        // Ejemplo: si tu InventarioAlmacen guarda las cantidades unificadas
        $inventario = InventarioAlmacen::where('producto_almacen_id', $id)->first();
        $stock = $inventario ? $inventario->cantidad : 0;
        return response()->json(['stock' => $stock]);
    }

}
