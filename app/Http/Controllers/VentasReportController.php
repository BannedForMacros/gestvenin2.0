<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Local;

class VentasReportController extends Controller
{
    public function index()
    {
        $locales = Local::all(); // Cargar locales para el filtro
        return view('reportes.reporte_ventas', compact('locales'));
    }

    public function metodoPagoMasUtilizado(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $localId = $request->input('local_id');
    
        $query = DB::table('metodo_pago_cierre_caja')
            ->join('cierre_caja', 'metodo_pago_cierre_caja.cierre_caja_id', '=', 'cierre_caja.id')
            ->select('metodo_pago_cierre_caja.metodo as nombre', DB::raw('SUM(metodo_pago_cierre_caja.monto) as total'))
            ->groupBy('metodo_pago_cierre_caja.metodo')
            ->orderByDesc('total');
    
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('cierre_caja.fecha_cierre', [$fechaInicio, $fechaFin]);
        }
        if ($localId) {
            $query->where('cierre_caja.local_id', $localId);
        }
    
        $data = $query->get();
        return response()->json($data);
    }
    
    public function ventasPorLocal(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
    
        $query = DB::table('cierre_caja')
            ->join('locales', 'cierre_caja.local_id', '=', 'locales.id')
            ->select('locales.nombre_local as nombre', DB::raw('SUM(cierre_caja.total_ventas) as total'))
            ->groupBy('locales.nombre_local')
            ->orderByDesc('total');
    
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('cierre_caja.fecha_cierre', [$fechaInicio, $fechaFin]);
        }
    
        $data = $query->get();
        return response()->json($data);
    }
    

    public function productoMasVendido(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $localId = $request->input('local_id');
    
        $query = DB::table('detalle_cierre_caja')
            ->join('cierre_caja', 'detalle_cierre_caja.cierre_caja_id', '=', 'cierre_caja.id')
            ->select('detalle_cierre_caja.producto as nombre', DB::raw('SUM(detalle_cierre_caja.cantidad) as total'))
            ->groupBy('detalle_cierre_caja.producto')
            ->orderByDesc('total');
    
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('cierre_caja.fecha_cierre', [$fechaInicio, $fechaFin]);
        }
        if ($localId) {
            $query->where('cierre_caja.local_id', $localId);
        }
    
        $data = $query->get();
        return response()->json($data);
    }
    
    
    

    // Tendencia de ingresos
    public function tendenciaIngresos(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $localId = $request->input('local_id');

        $query = DB::table('cierre_caja')
            ->select(
                DB::raw('DATE(fecha_cierre) as fecha'),
                DB::raw('SUM(total_ventas - total_gastos) as ingresos') // Calcula el balance final como ingresos
            )
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_cierre', [$fechaInicio, $fechaFin]);
        }
        if ($localId) {
            $query->where('local_id', $localId);
        }

        return response()->json($query->get());
    }

    public function comparativaVentasDias(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $localId = $request->input('local_id');

        $query = DB::table('cierre_caja')
            ->select(DB::raw('DATE(fecha_cierre) as fecha'), DB::raw('SUM(total_ventas) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_cierre', [$fechaInicio, $fechaFin]);
        }

        if ($localId) {
            $query->where('local_id', $localId);
        }

        return response()->json($query->get());
    }
    public function pollosVendidos(Request $request)
    {
        try {
            $fechaInicio = $request->input('fechaInicio');
            $fechaFin = $request->input('fechaFin');
            $localId = $request->input('local_id');
    
            $query = DB::table('cierre_caja')
                ->select(DB::raw('SUM(total_pollos_vendidos) as total'), DB::raw('DATE(fecha_cierre) as fecha'))
                ->groupBy('fecha')
                ->orderBy('fecha', 'asc');
    
            if ($fechaInicio && $fechaFin) {
                $query->whereBetween('fecha_cierre', [$fechaInicio, $fechaFin]);
            }
    
            if ($localId) {
                $query->where('local_id', $localId);
            }
    
            $data = $query->get();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
  public function ingresosEgresos(Request $request) 
  {
      try {
          $mes = $request->query('mes', date('m'));
          $año = date('Y');
  
          // 🔹 **Definir rango de fechas**
          if ($mes !== 'all') {
              $fechaInicio = "$año-$mes-01";
              $fechaFin = date("Y-m-t", strtotime($fechaInicio)); // Último día del mes
          } else {
              $fechaInicio = now()->subDays(30)->format('Y-m-d');
              $fechaFin = now()->format('Y-m-d');
          }
  
          // 🔹 **Generar todas las fechas del mes**
          $rangoFechas = [];
          $fechaTemp = strtotime($fechaInicio);
          $fechaLimite = strtotime($fechaFin);
  
          while ($fechaTemp <= $fechaLimite) {
              $fechaFormato = date("Y-m-d", $fechaTemp);
              $rangoFechas[$fechaFormato] = [
                  'fecha' => $fechaFormato,
                  'ingresos' => 0,
                  'egresos' => 0,
                  'gasto_almacen' => 0
              ];
              $fechaTemp = strtotime("+1 day", $fechaTemp);
          }
  
          // 🔹 **Consulta: Ingresos**
          $ingresos = DB::table('cierre_caja')
              ->select(DB::raw('SUM(total_ventas) as ingresos'), DB::raw('DATE(fecha_cierre) as fecha'))
              ->whereBetween('fecha_cierre', [$fechaInicio, $fechaFin])
              ->groupBy('fecha')
              ->get();
  
          foreach ($ingresos as $item) {
              if (isset($rangoFechas[$item->fecha])) {
                  $rangoFechas[$item->fecha]['ingresos'] = $item->ingresos;
              }
          }
  
          // 🔹 **Consulta: Egresos**
          $egresos = DB::table('gastos')
              ->select(DB::raw('SUM(monto) as egresos'), DB::raw('DATE(fecha_gasto) as fecha'))
              ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
              ->groupBy('fecha')
              ->get();
  
          foreach ($egresos as $item) {
              if (isset($rangoFechas[$item->fecha])) {
                  $rangoFechas[$item->fecha]['egresos'] = $item->egresos;
              }
          }
  
          // 🔹 **Consulta: Gastos de almacén**
          $gastosAlmacen = DB::table('entradas_almacen')
              ->select(DB::raw('SUM(total_gasto) as gasto_almacen'), DB::raw('DATE(fecha_entrada) as fecha'))
              ->whereBetween('fecha_entrada', [$fechaInicio, $fechaFin])
              ->groupBy('fecha')
              ->get();
  
          foreach ($gastosAlmacen as $item) {
              if (isset($rangoFechas[$item->fecha])) {
                  $rangoFechas[$item->fecha]['gasto_almacen'] = $item->gasto_almacen;
              }
          }
  
          return response()->json(array_values($rangoFechas), 200);
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
  }
  
  public function ventasPorLocales(Request $request)
  {
      $localesNombres = $request->query('locales'); // Ejemplo: 'Brisas,Belaunde'
      $fechaInicio = $request->query('fechaInicio');
      $fechaFin = $request->query('fechaFin');
      $mes = $request->query('mes'); // Nuevo parámetro opcional
  
      $localesArray = explode(',', $localesNombres);
  
      $query = DB::table('cierre_caja')
          ->join('locales', 'cierre_caja.local_id', '=', 'locales.id')
          ->select(
              'locales.nombre_local',
              DB::raw('DATE(cierre_caja.fecha_cierre) as fecha'),
              DB::raw('SUM(cierre_caja.balance_final) as balance_final')
          )
          ->whereIn('locales.nombre_local', $localesArray);
  
      // Filtrar por rango de fechas si se proporciona
      if ($fechaInicio && $fechaFin) {
          $query->whereBetween('cierre_caja.fecha_cierre', [$fechaInicio, $fechaFin]);
      }
  
      // Filtrar por mes si se proporciona
      if ($mes && $mes !== 'all') {
          $query->whereMonth('cierre_caja.fecha_cierre', $mes);
      }
  
      $data = $query->groupBy('locales.nombre_local', 'fecha')
          ->orderBy('fecha', 'asc')
          ->get();
  
      return response()->json($data);
  }
    

}
