<?php

namespace App\Http\Controllers;

use App\Models\HistorialInventario;
use App\Models\DetalleHistorialInventario;
use Illuminate\Http\Request;

class HistorialInventarioController extends Controller
{
    // Mostrar el listado de inventarios con agrupación por fecha
    // Mostrar el listado de inventarios con agrupación por fecha
    public function index(Request $request)
    {
        $fechaSeleccionada = $request->input('fecha', now()->toDateString());

        // Obtener el historial del inventario para la fecha seleccionada
        $historiales = HistorialInventario::where('fecha', $fechaSeleccionada)->get();

        return view('historial_inventario.index', compact('historiales', 'fechaSeleccionada'));
    }

    // Mostrar el detalle de un inventario específico
    public function show($id)
    {
        // Obtener el historial
        $historial = HistorialInventario::findOrFail($id);
    
        // Agrupar detalles por producto
        $detallesAgrupados = DetalleHistorialInventario::with('productoAlmacen')
            ->where('historial_inventario_id', $id)
            ->get()
            ->groupBy('productoAlmacen.codigo')
            ->map(function ($detalles) {
                $producto = $detalles->first()->productoAlmacen;
                return [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'cantidad_total' => $detalles->sum('cantidad'),
                    'lotes' => $detalles->pluck('lote')->filter()->join(', '),
                    'precio_unitario_promedio' => $detalles->avg('precio_unitario'),
                    'dinero_invertido' => $detalles->sum(function ($detalle) {
                        return $detalle->cantidad * $detalle->precio_unitario;
                    }),
                ];
            });
    
        return view('historial_inventario.show', compact('historial', 'detallesAgrupados'));
    }
    
    
}
