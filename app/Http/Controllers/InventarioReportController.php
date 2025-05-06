<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\DetalleEntrada;
use App\Models\DetalleSalidas;
use App\Models\ProductoAlmacen;
use Illuminate\Http\Request;


class InventarioReportController extends Controller
{
    /**
     * Producto con más salida
     */
    public function masSalida(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
    
        $query = DB::table('detalles_salidas')
            ->join('salidas_almacen', 'detalles_salidas.salida_almacen_id', '=', 'salidas_almacen.id')
            ->join('productos_almacen', 'detalles_salidas.producto_almacen_id', '=', 'productos_almacen.id')
            ->select(
                'productos_almacen.nombre',
                DB::raw('SUM(detalles_salidas.cantidad) as total_salidas')
            )
            ->groupBy('productos_almacen.id', 'productos_almacen.nombre')
            ->orderByDesc('total_salidas');
    
        // Filtrar por la fecha de la tabla salidas_almacen
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('salidas_almacen.fecha_salida', [$fechaInicio, $fechaFin]);
        }
    
        $data = $query->take(10)->get();
        return response()->json($data);
    }
    
    /**
     * Producto con más entrada
     */
    public function masEntrada(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
    
        $query = DB::table('detalle_entrada')
            ->join('entradas_almacen', 'detalle_entrada.entrada_almacen_id', '=', 'entradas_almacen.id')
            ->join('productos_almacen', 'detalle_entrada.producto_almacen_id', '=', 'productos_almacen.id')
            ->select(
                'productos_almacen.nombre',
                DB::raw('SUM(detalle_entrada.cantidad_entrada) as total_entradas')
            )
            ->groupBy('productos_almacen.id', 'productos_almacen.nombre')
            ->orderByDesc('total_entradas');
    
        // Filtrar por la fecha de la tabla entradas_almacen
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('entradas_almacen.fecha_entrada', [$fechaInicio, $fechaFin]);
        }
    
        $data = $query->take(10)->get();
        return response()->json($data);
    }
    
    /**
     * Producto con más rotación
     */
    public function masRotativo(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');

        $query = DB::table('productos_almacen')
            ->leftJoin('detalle_entrada', 'productos_almacen.id', '=', 'detalle_entrada.producto_almacen_id')
            ->leftJoin('entradas_almacen', 'detalle_entrada.entrada_almacen_id', '=', 'entradas_almacen.id')
            ->leftJoin('detalles_salidas', 'productos_almacen.id', '=', 'detalles_salidas.producto_almacen_id')
            ->leftJoin('salidas_almacen', 'detalles_salidas.salida_almacen_id', '=', 'salidas_almacen.id')
            ->select(
                'productos_almacen.nombre',
                DB::raw('COALESCE(SUM(detalle_entrada.cantidad_entrada), 0) as total_entradas'),
                DB::raw('COALESCE(SUM(detalles_salidas.cantidad), 0) as total_salidas'),
                DB::raw('COALESCE(SUM(detalles_salidas.cantidad), 0) 
                    + COALESCE(SUM(detalle_entrada.cantidad_entrada), 0) as total_movimiento')
            )
            ->groupBy('productos_almacen.id', 'productos_almacen.nombre')
            ->orderByDesc('total_movimiento');

        // Filtrar por las fechas de las tablas entradas_almacen y salidas_almacen
        if ($fechaInicio && $fechaFin) {
            $query->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('entradas_almacen.fecha_entrada', [$fechaInicio, $fechaFin])
                  ->orWhereBetween('salidas_almacen.fecha_salida', [$fechaInicio, $fechaFin]);
            });
        }

        $data = $query->take(10)->get();
        return response()->json($data);
    }

    /**
     * Productos con menor stock
     */
    public function menorStock()
    {
        $data = DB::table('inventario_almacen')
            ->join('productos_almacen', 'inventario_almacen.producto_almacen_id', '=', 'productos_almacen.id')
            ->select(
                'productos_almacen.nombre',
                'inventario_almacen.cantidad as total_stock'
            )
            ->orderBy('inventario_almacen.cantidad', 'asc')
            ->take(10)
            ->get();
    
        return response()->json($data);
    }
    
    /**
     * Productos sin movimiento
     */
    public function sinMovimiento(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
    
        $query = DB::table('productos_almacen')
            ->leftJoin('detalles_salidas', 'productos_almacen.id', '=', 'detalles_salidas.producto_almacen_id')
            ->leftJoin('salidas_almacen', 'detalles_salidas.salida_almacen_id', '=', 'salidas_almacen.id')
            ->select(
                'productos_almacen.nombre',
                DB::raw('COALESCE(SUM(detalles_salidas.cantidad), 0) as total_salidas')
            )
            ->groupBy('productos_almacen.id', 'productos_almacen.nombre')
            ->havingRaw('total_salidas = 0');
    
        // Filtrar por la fecha de la tabla salidas_almacen
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('salidas_almacen.fecha_salida', [$fechaInicio, $fechaFin]);
        }
    
        $data = $query->get();
        return response()->json($data);
    }

    // Mostrar vista principal del reporte
    public function inventario()
    {
        // Recuperar productos y categorías
        $productos = ProductoAlmacen::all();
        $categorias = Categoria::all();

        // Retornar la vista general del reporte
        return view('reportes.reporte_inventario', compact('productos', 'categorias'));
    }

    // Vista principal del reporte detallado
    public function vistaDetallado()
    {
        $productos = ProductoAlmacen::all();
        $categorias = Categoria::all();

        return view('reportes.reporte_detallado', compact('productos', 'categorias'));
    }

    /**
     * Reporte detallado por producto (entradas, salidas o combinado)
     */

    public function reporteProducto(Request $request)
    {
        $productoId  = $request->query('productoId');
        $tipo        = $request->query('tipo');      // 'entradas', 'salidas' o 'combinado'
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin    = $request->query('fechaFin');
    
        if (!$productoId) {
            return response()->json(['error' => 'Debe seleccionar un producto.'], 400);
        }
    
        // ——— Entradas ———
        $entradasQuery = DB::table('detalle_entrada')
            ->join('entradas_almacen', 'detalle_entrada.entrada_almacen_id', '=', 'entradas_almacen.id')
            ->where('detalle_entrada.producto_almacen_id', $productoId)
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('entradas_almacen.fecha_entrada', [$fechaInicio, $fechaFin]);
            })
            ->select([
                DB::raw('DATE(entradas_almacen.fecha_entrada) as fecha'),
                'detalle_entrada.cantidad_entrada as cantidad',
                DB::raw('"Entrada" as motivo'),
                'detalle_entrada.comprobante'
            ])
            ->orderBy('entradas_almacen.fecha_entrada', 'desc');
    
        // ——— Salidas ———
        $salidasQuery = DB::table('detalles_salidas')
            ->leftJoin('salidas_almacen', 'detalles_salidas.salida_almacen_id', '=', 'salidas_almacen.id')
            ->leftJoin('locales', 'salidas_almacen.local_id', '=', 'locales.id')
            ->where('detalles_salidas.producto_almacen_id', $productoId)
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('salidas_almacen.fecha_salida', [$fechaInicio, $fechaFin]);
            })
            ->select([
                DB::raw('DATE(salidas_almacen.fecha_salida) as fecha'),
                'detalles_salidas.cantidad',
                'salidas_almacen.motivo',
                DB::raw('COALESCE(locales.nombre_local, "N/A") as local'),
                DB::raw('NULL as comprobante')
            ])
            ->orderBy('salidas_almacen.fecha_salida', 'desc');
    
        // Ejecutar según tipo
        $entradas = collect();
        $salidas  = collect();
        if (in_array($tipo, ['entradas', 'combinado'])) {
            $entradas = $entradasQuery->get();
        }
        if (in_array($tipo, ['salidas', 'combinado'])) {
            $salidas = $salidasQuery->get();
        }
    
        $totalEntradas = $entradas->sum('cantidad');
        $totalSalidas  = $salidas->sum('cantidad');
    
        return response()->json([
            'entradas'      => $entradas,
            'salidas'       => $salidas,
            'totalEntradas' => $totalEntradas,
            'totalSalidas'  => $totalSalidas,
        ]);
    }
    
    public function reportePorCategoria(Request $request)
    {
        $categoriaId = $request->query('categoriaId');
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin    = $request->query('fechaFin');
    
        if (!$categoriaId) {
            return response()->json(['error' => 'Debe seleccionar una categoría.'], 400);
        }
    
        // ——— Entradas por categoría ———
        $entradas = DB::table('productos_almacen')
            ->join('detalle_entrada', 'productos_almacen.id', '=', 'detalle_entrada.producto_almacen_id')
            ->join('entradas_almacen', 'detalle_entrada.entrada_almacen_id', '=', 'entradas_almacen.id')
            ->where('productos_almacen.categoria_id', $categoriaId)
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('entradas_almacen.fecha_entrada', [$fechaInicio, $fechaFin]);
            })
            ->select([
                DB::raw('DATE(entradas_almacen.fecha_entrada) as fecha'),
                DB::raw('SUM(detalle_entrada.cantidad_entrada) as cantidad'),
                DB::raw('"Entrada" as motivo'),
            ])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();
    
        // ——— Salidas por categoría ———
        $salidas = DB::table('productos_almacen')
            ->join('detalles_salidas', 'productos_almacen.id', '=', 'detalles_salidas.producto_almacen_id')
            ->join('salidas_almacen', 'detalles_salidas.salida_almacen_id', '=', 'salidas_almacen.id')
            ->where('productos_almacen.categoria_id', $categoriaId)
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('salidas_almacen.fecha_salida', [$fechaInicio, $fechaFin]);
            })
            ->select([
                DB::raw('DATE(salidas_almacen.fecha_salida) as fecha'),
                DB::raw('SUM(detalles_salidas.cantidad) as cantidad'),
                'salidas_almacen.motivo',
            ])
            ->groupBy('fecha', 'motivo')
            ->orderBy('fecha')
            ->get();
    
        return response()->json([
            'entradas' => $entradas,
            'salidas'  => $salidas,
        ]);
    }
    

    /**
     * Movimientos del último mes (entradas y salidas)
     */
    public function obtenerMovimientosUltimoMes()
    {
        // Entradas último mes
        $entradas = DB::table('detalle_entrada')
            ->join('entradas_almacen', 'detalle_entrada.entrada_almacen_id', '=', 'entradas_almacen.id')
            ->select(
                DB::raw('DATE(entradas_almacen.fecha_entrada) as fecha'),
                DB::raw('SUM(detalle_entrada.cantidad_entrada) as total')
            )
            ->whereBetween('entradas_almacen.fecha_entrada', [now()->subMonth(), now()])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Salidas último mes
        $salidas = DB::table('detalles_salidas')
            ->join('salidas_almacen', 'detalles_salidas.salida_almacen_id', '=', 'salidas_almacen.id')
            ->select(
                DB::raw('DATE(salidas_almacen.fecha_salida) as fecha'),
                DB::raw('SUM(detalles_salidas.cantidad) as total')
            )
            ->whereBetween('salidas_almacen.fecha_salida', [now()->subMonth(), now()])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return response()->json(['entradas' => $entradas, 'salidas' => $salidas]);
    }

}

