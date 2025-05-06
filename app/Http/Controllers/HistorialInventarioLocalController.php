<?php

namespace App\Http\Controllers;

use App\Models\HistorialInventarioLocal;
use App\Models\DetalleHistorialInventarioLocal;
use App\Models\Local;
use Illuminate\Http\Request;

class HistorialInventarioLocalController extends Controller
{
    // Mostrar el historial agrupado por fecha y local
    public function index(Request $request)
    {
        $fechaSeleccionada = $request->input('fecha', now()->toDateString());
        $localId = $request->input('local_id');

        // Obtener los historiales agrupados por fecha y local
        $historiales = HistorialInventarioLocal::with('local')
            ->when($fechaSeleccionada, function ($query) use ($fechaSeleccionada) {
                $query->where('fecha', $fechaSeleccionada);
            })
            ->when($localId, function ($query) use ($localId) {
                $query->where('local_id', $localId);
            })
            ->get();

        // Obtener lista de locales para el filtro
        $locales = Local::all();

        return view('historial_inventario_local.index', compact('historiales', 'locales', 'fechaSeleccionada', 'localId'));
    }

    // Mostrar el detalle de un historial de inventario local
    public function show($id)
    {
        // Obtener el historial seleccionado
        $historial = HistorialInventarioLocal::with('local')->findOrFail($id);

        // Obtener los detalles asociados
        $detalles = DetalleHistorialInventarioLocal::with('productoAlmacen')
            ->where('historial_inventario_local_id', $id)
            ->get();

        return view('historial_inventario_local.show', compact('historial', 'detalles'));
    }
}
