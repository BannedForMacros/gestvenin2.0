<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GastosReportController extends Controller
{
    public function gastosTotales(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
        $tipoGasto = $request->query('tipoGasto');
        $clasificacion = $request->query('clasificacion');

        $query = DB::table('gastos')
            ->select(DB::raw('DATE(fecha_gasto) as fecha'), DB::raw('SUM(monto) as total'))
            ->where('activo', 1)
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin]);
        }
        if ($tipoGasto) {
            $query->where('tipo_gasto_id', $tipoGasto);
        }
        if ($clasificacion) {
            $query->where('clasificacion_gasto_id', $clasificacion);
        }

        return response()->json($query->get());
    }

    public function comparativaGastos(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');

        $query = DB::table('gastos')
            ->join('clasificaciones_gastos', 'gastos.clasificacion_gasto_id', '=', 'clasificaciones_gastos.id')
            ->join('tipos_gastos', 'gastos.tipo_gasto_id', '=', 'tipos_gastos.id')
            ->select(
                DB::raw("CONCAT(tipos_gastos.nombre, ' - ', clasificaciones_gastos.nombre) as clasificacion"),
                DB::raw('SUM(gastos.monto) as total')
            )
            ->where('gastos.activo', 1)
            ->groupBy('clasificacion')
            ->orderByDesc('total');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('gastos.fecha_gasto', [$fechaInicio, $fechaFin]);
        }

        return response()->json($query->get());
    }

    public function gastosPorTipo(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
        $tipoGasto = $request->query('tipoGasto');

        $query = DB::table('gastos')
            ->join('tipos_gastos', 'gastos.tipo_gasto_id', '=', 'tipos_gastos.id')
            ->select('tipos_gastos.nombre as tipo', DB::raw('SUM(gastos.monto) as total'))
            ->where('gastos.activo', 1)
            ->groupBy('tipos_gastos.nombre')
            ->orderByDesc('total');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('gastos.fecha_gasto', [$fechaInicio, $fechaFin]);
        }
        if ($tipoGasto) {
            $query->where('gastos.tipo_gasto_id', $tipoGasto);
        }

        return response()->json($query->get());
    }

    public function gastosPorClasificacion(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
        $clasificacion = $request->query('clasificacion');

        $query = DB::table('gastos')
            ->join('clasificaciones_gastos', 'gastos.clasificacion_gasto_id', '=', 'clasificaciones_gastos.id')
            ->join('tipos_gastos', 'gastos.tipo_gasto_id', '=', 'tipos_gastos.id')
            ->select(
                DB::raw("CONCAT(tipos_gastos.nombre, ' - ', clasificaciones_gastos.nombre) as clasificacion"), 
                DB::raw('SUM(gastos.monto) as total')
            )
            ->where('gastos.activo', 1)
            ->groupBy('clasificacion')
            ->orderByDesc('total');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('gastos.fecha_gasto', [$fechaInicio, $fechaFin]);
        }
        if ($clasificacion) {
            // Filtramos por ID de la clasificación
            $query->where('gastos.clasificacion_gasto_id', $clasificacion);
        }

        return response()->json($query->get());
    }

    public function gastosVentas(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');
        $localId = $request->query('local_id');

        $query = DB::table('gastos_ventas as gv')
            ->join('locales as l', 'gv.local_id', '=', 'l.id')
            ->where('gv.activo', 1);

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('gv.fecha_gasto', [$fechaInicio, $fechaFin]);
        }

        if ($localId) {
            // Si se elige un local, mostramos por clasificación
            $query->join('clasificaciones_gastos as cg', 'gv.clasificacion_gasto_id', '=', 'cg.id')
                ->select('cg.nombre as label', DB::raw('SUM(gv.monto) as total'))
                ->where('gv.local_id', $localId)
                ->groupBy('cg.nombre')
                ->orderByDesc('total');
        } else {
            // Si no se filtra por local, agrupamos por local
            $query->select('l.nombre as label', DB::raw('SUM(gv.monto) as total'))
                ->groupBy('l.nombre')
                ->orderByDesc('total');
        }

        return response()->json($query->get());
    }
}
