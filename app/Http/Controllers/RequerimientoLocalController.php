<?php

namespace App\Http\Controllers;

use App\Models\DetalleRequerimientoLocal;
use App\Models\Local;
use App\Models\ProductoAlmacen;
use App\Models\RequerimientoLocal;
use App\Models\InventarioLocal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class RequerimientoLocalController extends Controller
{
// app/Http/Controllers/RequerimientoLocalController.php

public function index(Request $request)
{
    $user = Auth::user();
    $rol  = DB::table('model_has_roles')
              ->where('model_id', $user->id)
              ->value('role_id');

    // Siempre cargamos todos los locales para el filtro
    $locales = Local::all();

    $query = RequerimientoLocal::with(['detalles.productoAlmacen', 'local']);

    if (in_array($rol, [1,2])) {
        // Admin / dueño: opcionalmente filtran por local
        $query->when($request->local_id, fn($q, $loc) => $q->where('local_id', $loc));
    }
    elseif ($rol == 3) {
        // Cajera: sólo sus pendientes y en proceso
        $query->where('local_id', $user->local_id)
              ->whereIn('estado', ['pendiente','no_atendido']);
    }
    else {
        // Logística: sólo en proceso y atendidos
        $query->whereIn('estado', ['no_atendido','atendido']);
    }

    $requerimientos = $query
        ->orderBy('fecha_requerimiento','desc')
        ->get();

    $productos = ProductoAlmacen::all();

    return Inertia::render('RequerimientosLocal/Index', [
        'requerimientos' => $requerimientos,
        'locales'        => $locales,
        'productos'      => $productos,
    ]);
}

    

    public function create()
    {
        $locales   = Local::all();
        $productos = ProductoAlmacen::all();
    
        // Si es cajera, guardamos su local_id; sino null (el Select mostrará todos)
        $roleName      = Auth::user()->roles->pluck('name')->first();
        $localAsignado = $roleName === 'cajera'
            ? Auth::user()->local_id
            : null;
    
        return Inertia::render('RequerimientosLocal/Create', [
            'locales'       => $locales,
            'productos'     => $productos,
            'localAsignado' => $localAsignado,
        ]);
    }
    

    public function store(Request $request)
    {

        
        // Crear un nuevo requerimiento con los detalles ingresados por el usuario
        $requerimiento = new RequerimientoLocal();
        $requerimiento->local_id = $request->input('local_id');
        $requerimiento->estado = 'pendiente'; 
        $requerimiento->observaciones = $request->input('observaciones');
        $requerimiento->fecha_requerimiento = now();
        $requerimiento->save();

        // Agregar los detalles del requerimiento
        foreach ($request->input('detalles') as $detalle) {
            $requerimiento->detalles()->create([
                'producto_almacen_id' => $detalle['producto_almacen_id'],
                'cantidad_enviada' => 0, // Inicialmente no enviado
            ]);
        }

        return redirect()->route('requerimientos_local.index')->with('success', 'Requerimiento registrado.');
    }
    
    public function edit(RequerimientoLocal $requerimiento)
    {
        // Cargamos la relación "local"
        $requerimiento->load(['local', 'detalles.productoAlmacen']);
    
        // ... luego tu código tal cual estaba,
        // simplemente usas $requerimiento->toArray() y ya incluye ['local'=>[…]]
        $locales       = Local::all();
        $productos     = ProductoAlmacen::all();
        $roleName      = Auth::user()->roles->pluck('name')->first();
        $localAsignado = $roleName === 'cajera' ? Auth::user()->local_id : null;
    
        $detalles = $requerimiento->detalles->map(fn($d) => [
            'producto_almacen_id' => $d->producto_almacen_id,
            'nombre_producto'     => $d->productoAlmacen->nombre,
        ])->toArray();
    
        return Inertia::render('RequerimientosLocal/RequerimientoModal', [
            'requerimiento' => array_merge($requerimiento->toArray(), ['detalles' => $detalles]),
            'locales'       => $locales,
            'productos'     => $productos,
            'localAsignado' => $localAsignado,
        ]);
    }
    
    


public function update(Request $request, $requerimientoId)
{
    // 0) Diagnóstico de la URL y el parámetro bruto
    Log::debug('🌐 URL completa → ' . $request->fullUrl());
    Log::debug('🔑 Param route("requerimiento") → ' . $request->route('requerimiento'));

    // 1) Cargamos el modelo a mano (fallará con 404 si no existe)
    $requerimiento = RequerimientoLocal::with('detalles')->findOrFail($requerimientoId);
    Log::debug('📦 Modelo cargado manualmente', [
        'exists'     => true,
        'id'         => $requerimiento->id,
        'attributes' => $requerimiento->getAttributes(),
    ]);

    // 2) Validación
    $data = $request->validate([
        'local_id'                       => 'required|exists:locales,id',
        'observaciones'                  => 'nullable|string|max:500',
        'detalles'                       => 'required|array|min:1',
        'detalles.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
        'detalles.*.cantidad_requerida'  => 'nullable|numeric|min:1',
    ]);

    // 3) Listener para ver cada query (opcional)
    DB::listen(function ($query) {
        Log::debug('🪄 SQL', [
            'sql'      => $query->sql,
            'bindings' => $query->bindings,
            'time_ms'  => $query->time,
        ]);
    });

    try {
        DB::beginTransaction();

        // 4) Actualizamos la cabecera
        $requerimiento->update([
            'local_id'      => $data['local_id'],
            'observaciones' => $data['observaciones'] ?? '',
        ]);
        Log::info('✅ Cabecera actualizada', $requerimiento->only(['id','local_id','observaciones']));

        // 5) Eliminamos viejos detalles y los contamos
        $countOld = $requerimiento->detalles()->count();
        $requerimiento->detalles()->delete();
        Log::info("🧹 Detalles eliminados: {$countOld}");

        // 6) Insertamos los nuevos
        foreach ($data['detalles'] as $det) {
            $newDet = $requerimiento->detalles()->create([
                'producto_almacen_id' => $det['producto_almacen_id'],
                'cantidad_requerida'  => $det['cantidad_requerida'] ?? 1,
            ]);
            Log::info('➕ Detalle creado', $newDet->toArray());
        }

        DB::commit();
        Log::info('🎉 Requerimiento actualizado OK', ['id' => $requerimiento->id]);

        return redirect()
            ->route('requerimientos_local.index')
            ->with('success', "Requerimiento #{$requerimiento->id} actualizado.");

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('💥 Error al actualizar', [
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ]);

        return back()
            ->withInput()
            ->with('error', 'Ocurrió un error: '.$e->getMessage());
    }
}



    public function generarRequerimientos(Request $request)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();
        $local_id = $user->local_id;
    
        // Si es dueño o admin, puede seleccionar el local manualmente
        $userRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->select('roles.name')
            ->first();
    
        if ($userRole && ($userRole->name === 'dueño' || $userRole->name === 'admin')) {
            $local_id = $request->get('local_id');
        }
    
        // Obtener los productos por debajo de la cantidad mínima en el local correspondiente
        $productosFaltantes = InventarioLocal::with(['productoAlmacen'])
            ->join('configuracion_minimos_local', function($join) use ($local_id) {
                $join->on('inventario_local.producto_almacen_id', '=', 'configuracion_minimos_local.producto_almacen_id')
                    ->where('configuracion_minimos_local.local_id', '=', $local_id);
            })
            ->where('inventario_local.local_id', $local_id)
            ->whereColumn('inventario_local.cantidad', '<', 'configuracion_minimos_local.cantidad_minima')
            ->select('inventario_local.*', 'configuracion_minimos_local.cantidad_minima')
            ->get();
    
        // Verificar si es una solicitud AJAX
        if ($request->ajax() || $request->wantsJson()) {
            // Generar el requerimiento si hay productos faltantes
            if ($productosFaltantes->isNotEmpty()) {
                $requerimiento = RequerimientoLocal::create([
                    'local_id' => $local_id,
                    'estado' => 'pendiente',
                    'fecha_requerimiento' => now(),
                    'observaciones' => 'Requerimiento automático por falta de stock',
                ]);
    
                // Añadir los productos al requerimiento
                foreach ($productosFaltantes as $producto) {
                    $requerimiento->detalles()->create([
                        'producto_almacen_id' => $producto->producto_almacen_id,
                        'cantidad_enviada' => 0, // No enviado aún
                    ]);
                }
    
                return response()->json([
                    'success' => true,
                    'message' => 'Requerimientos generados correctamente.',
                    'redirectUrl' => route('requerimientos_local.index')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'info' => true,
                    'message' => 'Todos los productos cumplen con el stock mínimo.'
                ]);
            }
        }
    
        // Manejo tradicional para solicitudes no AJAX
        if ($productosFaltantes->isNotEmpty()) {
            $requerimiento = RequerimientoLocal::create([
                'local_id' => $local_id,
                'estado' => 'pendiente',
                'fecha_requerimiento' => now(),
                'observaciones' => 'Requerimiento automático por falta de stock',
            ]);
    
            // Añadir los productos al requerimiento
            foreach ($productosFaltantes as $producto) {
                $requerimiento->detalles()->create([
                    'producto_almacen_id' => $producto->producto_almacen_id,
                    'cantidad_enviada' => 0, // No enviado aún
                ]);
            }
    
            return redirect()->route('requerimientos_local.index')->with('success', 'Requerimientos generados correctamente.');
        } else {
            return redirect()->back()->with('info', 'Todos los productos cumplen con el stock mínimo.');
        }
    }
    
    public function confirm($id)
    {
        try {
            $requerimiento = RequerimientoLocal::findOrFail($id);
    
            // Cambiar el estado a "no_atendido"
            $requerimiento->estado = 'no_atendido';
            $requerimiento->save();
    
            // Responder con JSON si todo salió bien
            return response()->json([
                'success' => true,
                'message' => 'El requerimiento fue enviado a logística exitosamente.',
            ]);
        } catch (\Exception $e) {
            // Manejar errores y devolver un JSON
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un problema al procesar la solicitud.',
            ], 500);
        }
    }
    
    
    

    public function show($id)
    {
        $requerimiento = RequerimientoLocal::with('detalles')->findOrFail($id);
    
        // Filtrar solo productos que están en el inventario del local
        $productos = InventarioLocal::where('local_id', $requerimiento->local_id)
            ->with('productoAlmacen')
            ->get()
            ->pluck('productoAlmacen')
            ->unique('id');
    
        // Retornar la vista con los detalles del requerimiento y los productos disponibles
        return view('requerimientos_local.show', compact('requerimiento', 'productos'));
    }

    public function agregarProducto(Request $request, $id)
    {
        $requerimiento = RequerimientoLocal::findOrFail($id);
    
        // Validar que el producto exista en el inventario antes de agregarlo
        $productoInventario = InventarioLocal::where('producto_almacen_id', $request->input('producto_id'))
            ->where('local_id', $requerimiento->local_id)
            ->first();
    
        if (!$productoInventario) {
            return redirect()->route('requerimientos_local.show', $requerimiento->id)
                ->with('error', 'El producto no está disponible en el inventario de este local.');
        }
    
        // Verificar si el producto ya está en el requerimiento
        $productoExistente = $requerimiento->detalles()->where('producto_almacen_id', $request->input('producto_id'))->exists();
        
        if ($productoExistente) {
            return redirect()->route('requerimientos_local.show', $requerimiento->id)
                ->with('error', 'El producto ya está en el requerimiento.');
        }
    
        // Agregar el producto al requerimiento
        $requerimiento->detalles()->create([
            'producto_almacen_id' => $request->input('producto_id'),
            'cantidad_enviada' => 0, // La cantidad enviada se actualizará luego
        ]);
    
        return redirect()->route('requerimientos_local.show', $requerimiento->id)
            ->with('success', 'Producto agregado al requerimiento.');
    }


    public function actualizarCantidadEnviada(Request $request, $detalleId)
{
    $detalle = DetalleRequerimientoLocal::findOrFail($detalleId);
    
    // Actualizar la cantidad enviada
    $detalle->cantidad_enviada = $request->input('cantidad_enviada');
    $detalle->save();

    return redirect()->back()->with('success', 'Cantidad enviada actualizada.');
}
public function atenderRequerimiento($id)
{
    $requerimiento = RequerimientoLocal::with('detalles.productoAlmacen')->findOrFail($id);
    $locales = Local::all();

    // Predefinir motivo a "local"
    $motivo = 'local';

    return view('salidas_almacen.create', [
        'requerimiento' => $requerimiento,
        'locales' => $locales,
        'motivo' => $motivo,  // Pasar el motivo a la vista
        'localSeleccionado' => $requerimiento->local_id // Preseleccionar el local
    ]);
}


public function actualizarObservaciones(Request $request, $id)
{
    $request->validate([
        'observaciones' => 'required|string|max:255',
    ]);

    $requerimiento = RequerimientoLocal::findOrFail($id);
    $requerimiento->observaciones = $request->observaciones;
    $requerimiento->save();

    return response()->json(['success' => 'Observaciones actualizadas correctamente']);
}



}
