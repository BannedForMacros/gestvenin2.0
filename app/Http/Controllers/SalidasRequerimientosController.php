<?php

namespace App\Http\Controllers;

use App\Models\DetalleRequerimientoLocal;
use App\Models\InventarioAlmacen;
use App\Models\SalidasAlmacen;
use App\Models\DetalleSalidas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalidasRequerimientosController extends Controller
{
    // Método para mostrar la vista de crear salida de requerimiento
    public function create()
    {
        // Revisar si hay un requerimiento en la sesión
        $requerimiento = session('requerimiento');
        $local = session('local');

        if ($requerimiento) {
            return view('salidas_requerimientos.create', compact('requerimiento', 'local'));
        }

        return redirect()->route('requerimientos_local.index')->with('error', 'No hay requerimiento seleccionado.');
    }

    // Método para almacenar la salida de requerimiento
    public function store(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'detalles.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
        ]);
    
        // Obtener el requerimiento de la sesión
        $requerimiento = session('requerimiento');
        if (!$requerimiento) {
            return back()->withErrors('No se encontró un requerimiento en la sesión.');
        }
    
        // Crear la salida de almacén específicamente para el requerimiento
        $salida = new SalidasAlmacen();
        $salida->usuario_id = Auth::id();
        $salida->local_id = $requerimiento->local->id; // Acceder al id del local desde la relación
        $salida->motivo = 'local'; // Salida para locales
        $salida->fecha_salida = now();
        $salida->observaciones = 'Salida para requerimiento local';
        $salida->estado = 1; // Activo por defecto
        $salida->save();
    
        // Iterar sobre los productos y reducir el stock de acuerdo a los lotes
        foreach ($request->detalles as $detalle) {
            $productoId = $detalle['producto_almacen_id'];
            $cantidadRequerida = $detalle['cantidad'];
    
            // Obtener todos los lotes disponibles para el producto
            $lotesDisponibles = InventarioAlmacen::where('producto_almacen_id', $productoId)
                ->where('cantidad', '>', 0)
                ->orderBy('fecha', 'asc')
                ->get();
    
            // Reducir la cantidad del stock en cada lote hasta completar la cantidad solicitada
            foreach ($lotesDisponibles as $lote) {
                if ($cantidadRequerida <= 0) {
                    break; // Ya no necesitamos más stock
                }
    
                if ($lote->cantidad >= $cantidadRequerida) {
                    // El lote tiene suficiente stock para cubrir la cantidad requerida
                    $lote->cantidad -= $cantidadRequerida;
                    $lote->save();
    
                    // Registrar la salida de este lote en el detalle de la salida
                    DetalleSalidas::create([
                        'salida_almacen_id' => $salida->id,
                        'producto_almacen_id' => $productoId,
                        'lote' => $detalle['lote'] ?? 'Sin lote', // Aquí se guarda el lote o un valor por defecto
                        'cantidad' => $cantidadRequerida,
                        'precio_unitario' => $lote->precio_unitario,
                        'precio_total' => $lote->precio_unitario * $cantidadRequerida,
                    ]);
    
                    $cantidadRequerida = 0; // Ya se ha cubierto todo
                } else {
                    // El lote no tiene suficiente stock, usar todo lo disponible y continuar con el siguiente lote
                    $cantidadRestante = $cantidadRequerida - $lote->cantidad;
    
                    // Registrar la salida de este lote en el detalle de la salida
                    DetalleSalidas::create([
                        'salida_almacen_id' => $salida->id,
                        'producto_almacen_id' => $productoId,
                        'lote' => $detalle['lote'] ?? 'Sin lote',
                        'cantidad' => $lote->cantidad,
                        'precio_unitario' => $lote->precio_unitario,
                        'precio_total' => $lote->precio_unitario * $lote->cantidad,
                    ]);
    
                    $lote->cantidad = 0; // Usar todo el stock de este lote
                    $lote->save();
    
                    $cantidadRequerida = $cantidadRestante; // Aún queda cantidad por cubrir
                }
            }
    
            // Si después de revisar todos los lotes, aún hay cantidad pendiente, lanzar una excepción
            // Si después de revisar todos los lotes, aún hay cantidad pendiente, lanzar una excepción
            if ($cantidadRequerida > 0) {
                return back()->withErrors(['msg' => 'No hay suficiente stock para el producto: ' . $productoId]);
            }

            // Verificar si el requerimiento está en la sesión
            if (session()->has('requerimiento')) {
                $requerimiento = session('requerimiento');
                $requerimientoId = $requerimiento['id'];
            } else {
                return back()->withErrors(['msg' => 'No hay requerimiento en la sesión.']);
            }
            

            // Actualizar la cantidad enviada en el requerimiento
            $detalleRequerimiento = DetalleRequerimientoLocal::where('requerimiento_local_id', $requerimientoId)
                ->where('producto_almacen_id', $productoId)
                ->first();

            if ($detalleRequerimiento) {
                $detalleRequerimiento->cantidad_enviada += $detalle['cantidad'];
                $detalleRequerimiento->save();
            }

        }
    
        return redirect()->route('salidas_almacen.index')->with('success', 'Salida de requerimiento registrada con éxito.');
    }
}
