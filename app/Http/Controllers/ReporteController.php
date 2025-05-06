<?php

namespace App\Http\Controllers;

use App\Models\ClasificacionGasto;
use App\Models\Gasto;
use Illuminate\Http\Request;
use App\Models\Local;
use App\Models\TipoGasto;

class ReporteController extends Controller
{
    /**
     * Muestra la página principal para seleccionar el tipo de reporte.
     */
    public function index()
    {
        return view('reportes.index'); // Vista principal para seleccionar tipo de reporte
    }

    /**
     * Muestra la vista del reporte de inventario.
     */
    public function inventario()
    {
        return view('reportes.reporte_inventario');
    }

    /**
     * Muestra la vista del reporte de ventas.
     */

    public function ventas()
    {
        $locales = Local::all(); // Obtener todos los locales
        return view('reportes.reporte_ventas', compact('locales'));
    }
    public function gastos()
    {
        $locales = \App\Models\Local::select('id', 'nombre_local')->get();
        $tiposGastos = \App\Models\TipoGasto::all();
    
        // Unimos clasificaciones con tipos de gastos para diferenciarlas
        $clasificaciones = \App\Models\ClasificacionGasto::select(
            'clasificaciones_gastos.id', 
            'clasificaciones_gastos.nombre as clasificacion_nombre', 
            'tipos_gastos.nombre as tipo_nombre'
        )
        ->join('tipos_gastos', 'clasificaciones_gastos.tipo_gasto_id', '=', 'tipos_gastos.id')
        ->orderBy('tipo_nombre', 'asc')
        ->orderBy('clasificacion_nombre', 'asc')
        ->get();
    
        return view('reportes.reporte_gastos', compact('locales', 'tiposGastos', 'clasificaciones'));
    }
    
    
    

    /**
     * Muestra la vista del reporte de auditoría.
     */
    public function auditoria()
    {
        return view('reportes.reporte_auditoria');
    }



    
}
