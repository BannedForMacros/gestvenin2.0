<?php

namespace App\Http\Controllers;

use App\Models\InventarioLocal;
use App\Models\SalidaLocal;
use App\Models\ProductoAlmacen;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalidaLocalController extends Controller
{
    // Mostrar el formulario para crear una nueva salida
// Mostrar formulario para registrar nueva salida de inventario
public function create()
{
    // Obtener los productos disponibles en el local de la cajera
    $localId = Auth::user()->local_id;
    
    // Utilizar el nombre correcto de la relación 'inventarioLocal'
    $productos = ProductoAlmacen::whereHas('inventarioLocal', function($query) use ($localId) {
        $query->where('local_id', $localId);
    })->get();

    return view('salidas_local.create', compact('productos'));
}


// Guardar una nueva salida de inventario
public function store(Request $request)
{
    // Validar los datos del formulario
    $validated = $request->validate([
        'producto_almacen_id' => 'required|exists:productos_almacen,id',
        'cantidad' => 'required|numeric|min:0.01',
        'tipo_salida' => 'required|in:descarte,fecha_vencimiento',
        'observacion' => 'nullable|string',
    ]);

    // Verificar que el producto esté disponible en el inventario local
    $localId = Auth::user()->local_id;
    $inventarioLocal = InventarioLocal::where('producto_almacen_id', $validated['producto_almacen_id'])
                                    ->where('local_id', $localId)
                                    ->first();

    if (!$inventarioLocal || $inventarioLocal->cantidad < $validated['cantidad']) {
        return redirect()->back()->withErrors(['error' => 'No hay suficiente stock en el inventario para esta salida.']);
    }

    // Registrar la salida
    SalidaLocal::create([
        'producto_almacen_id' => $validated['producto_almacen_id'],
        'local_id' => $localId,
        'cantidad' => $validated['cantidad'],
        'tipo_salida' => $validated['tipo_salida'],
        'observacion' => $validated['observacion'],
    ]);



    return redirect()->route('salidas_local.index')->with('success', 'Salida de inventario registrada con éxito.');
}

    // Mostrar la lista de salidas
    public function index(Request $request)
    {
        // Filtrar por local y por tipo de salida
        $query = SalidaLocal::query();

        if ($request->filled('local_id')) {
            $query->where('local_id', $request->local_id);
        }

        if ($request->filled('tipo_salida')) {
            $query->where('tipo_salida', $request->tipo_salida);
        }

        $salidas = $query->with('productoAlmacen', 'local')->get();

        return view('salidas_local.index', compact('salidas'));
    }
}
