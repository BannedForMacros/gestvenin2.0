<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscrepanciaInventarioLocal;
use App\Models\Local;
use Illuminate\Support\Facades\DB;

class DiscrepanciaInventarioLocalController extends Controller
{
    public function index(Request $request)
    {
        $fecha = $request->input('fecha') ?? now()->format('Y-m-d');
        $localId = $request->input('local_id');
    
        // Obtener los locales para el selector
        $locales = DB::table('locales')->get();
    
        // Verificar que se haya seleccionado un local
        if (!$localId) {
            return view('discrepancia_inventario_local.index', [
                'discrepancias' => [],
                'salidasManuales' => collect(),
                'locales' => $locales,
                'fecha' => $fecha,
                'localId' => null,
            ])->withErrors(['Debe seleccionar un local para ver las discrepancias.']);
        }
    
        // Obtener discrepancias con salidas manuales incluidas en el consumo teórico
        $discrepancias = DB::table('discrepancia_inventario_local')
            ->join('productos_almacen', 'discrepancia_inventario_local.producto_almacen_id', '=', 'productos_almacen.id')
            ->leftJoin('salidas_inventario_local', function ($join) use ($localId, $fecha) {
                $join->on('discrepancia_inventario_local.producto_almacen_id', '=', 'salidas_inventario_local.producto_almacen_id')
                    ->where('salidas_inventario_local.local_id', '=', $localId)
                    ->whereDate('salidas_inventario_local.created_at', '=', $fecha);
            })
            ->leftJoin('detalle_venta', 'discrepancia_inventario_local.producto_almacen_id', '=', 'detalle_venta.producto_id')
            ->leftJoin('ventas', function ($join) use ($localId, $fecha) {
                $join->on('detalle_venta.venta_id', '=', 'ventas.id')
                    ->where('ventas.local_id', '=', $localId)
                    ->where('ventas.activo', '=', true) // Solo ventas activas
                    ->whereDate('ventas.fecha_venta', '=', $fecha);
            })
            ->select(
                'productos_almacen.nombre as producto',
                DB::raw('discrepancia_inventario_local.consumo_teorico + IFNULL(SUM(salidas_inventario_local.cantidad), 0) as consumo_teorico'),
                'discrepancia_inventario_local.consumo_real',
                DB::raw('(discrepancia_inventario_local.consumo_real - (discrepancia_inventario_local.consumo_teorico + IFNULL(SUM(salidas_inventario_local.cantidad), 0))) as diferencia'),
                DB::raw('IFNULL(SUM(salidas_inventario_local.cantidad), 0) as salidas_manual')
            )
            ->where('discrepancia_inventario_local.local_id', $localId)
            ->whereDate('discrepancia_inventario_local.fecha', $fecha)
            ->groupBy(
                'productos_almacen.nombre',
                'discrepancia_inventario_local.consumo_teorico',
                'discrepancia_inventario_local.consumo_real',
                'discrepancia_inventario_local.producto_almacen_id'
            )
            ->get();
    
        // Obtener salidas manuales para la alerta
        $salidasManuales = DB::table('salidas_inventario_local')
            ->where('local_id', $localId)
            ->whereDate('created_at', $fecha)
            ->get();
    
        return view('discrepancia_inventario_local.index', compact('discrepancias', 'salidasManuales', 'locales', 'fecha', 'localId'));
    }
    
    
    
}
