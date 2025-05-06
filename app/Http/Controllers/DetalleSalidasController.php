<?php

namespace App\Http\Controllers;

use App\Models\DetalleSalidas;
use Illuminate\Http\Request;

class DetalleSalidasController extends Controller
{
    public function show($id)
    {
        // Mostrar el detalle de una salida específica
        $detalles = DetalleSalidas::where('salidas_almacen_id', $id)->get();
        return view('detalle_salidas.show', compact('detalles'));
    }
}
