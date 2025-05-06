<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaReportController extends Controller
{


    public function auditoria()
    {
        return view('reportes.reporte_auditoria');
    }
    public function ingresosEgresos(Request $request)
    {
        try {
            // Rango de fechas opcional, predeterminado a los últimos 30 días
            $fechaInicio = $request->query('fechaInicio', now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->query('fechaFin', now()->format('Y-m-d'));

            // Consulta para ingresos (de la tabla cierre_caja)
            $ingresosQuery = DB::table('cierre_caja')
                ->select(
                    DB::raw('SUM(total_ventas) as ingresos'),
                    DB::raw('DATE(fecha_cierre) as fecha')
                )
                ->whereBetween('fecha_cierre', [$fechaInicio, $fechaFin])
                ->groupBy('fecha');

            $ingresos = $ingresosQuery->get();

            // Consulta para egresos (de la tabla gastos)
            $egresosQuery = DB::table('gastos')
                ->select(
                    DB::raw('SUM(monto) as egresos'),
                    DB::raw('DATE(fecha_gasto) as fecha')
                )
                ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
                ->groupBy('fecha');

            $egresos = $egresosQuery->get();

            // Combinar datos
            $data = collect($ingresos)
                ->merge(collect($egresos))
                ->groupBy('fecha')
                ->map(function ($items, $fecha) {
                    $ingresos = $items->where('ingresos', '!=', null)->sum('ingresos');
                    $egresos = $items->where('egresos', '!=', null)->sum('egresos');

                    return [
                        'fecha' => $fecha,
                        'ingresos' => $ingresos,
                        'egresos' => $egresos,
                        'balance' => $ingresos - $egresos,
                    ];
                })
                ->values();

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
