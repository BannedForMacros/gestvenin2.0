<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Delivery;
use App\Models\DetalleVenta;
use App\Models\DetalleDelivery;
use App\Models\ProductoVenta;
use App\Models\Venta;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InsumosPorProducto;
use App\Models\InventarioLocal;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;

class VentasController extends Controller
{
    // Mostrar todas las ventas (filtrar por fecha y local)
    public function index(Request $request)
    {
        $query = Venta::where('activo', true);
    
        $fechaActual = now()->format('Y-m-d');
    
        // Filtrar por rol de usuario
        $roleId = DB::table('model_has_roles')
            ->where('model_id', Auth::user()->id)
            ->value('role_id');
    
        // Verificar si la caja está cerrada para el local y fecha actual
        $cajaCerrada = \App\Models\CierreCaja::where('local_id', Auth::user()->local_id)
            ->whereDate('fecha_cierre', $fechaActual)
            ->exists();
    
        if ($roleId == 3) { // Si el rol es cajera
            // Usar el local asignado a la cajera y la fecha actual
            $query->whereDate('fecha_venta', $fechaActual)
                  ->where('local_id', Auth::user()->local_id);
        } else {
            // Filtrar por fecha para roles dueño/admin
            if (!$request->filled('fecha_venta')) {
                $query->whereDate('fecha_venta', $fechaActual);
            } else {
                $query->whereDate('fecha_venta', $request->input('fecha_venta'));
            }
    
            // Filtrar por local si se selecciona uno
            if ($request->filled('local_id')) {
                $query->where('local_id', $request->input('local_id'));
            }
        }
    
        // Recuperar las ventas con las relaciones necesarias
        $ventas = $query
        ->with([
            'detalles.producto',
            'detalleDelivery',
            'local',    // ← asegura que $venta->local exista
            'pagos',    // ← si tu columna “Pagos” hace v.pagos.map(...)
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    
        // Recuperar los locales (solo para roles que no sean cajera)
        $locales = Local::all();
    
        return Inertia::render('Ventas/Index', [
            'ventas'      => $ventas,
            'locales'     => $locales,
            'fechaActual' => $fechaActual,   // ← usa la var que SÍ existe
            'cajaCerrada' => $cajaCerrada,
        ]);  
    }
    
    // Mostrar el formulario para crear una nueva venta
    public function create()
    {
        // Productos activos para la venta
        $productos   = ProductoVenta::where('estado', 1)->get();

        // Solo los locales que quieres exponer
        $locales     = Local::whereIn('nombre_local', ['Brisas', 'Belaunde'])->get();

        // Determinar el rol principal del usuario
        $roleName    = Auth::user()->roles->pluck('name')->first();

        // Si es cajera, solo su local asignado
        $local       = $roleName === 'cajera'
            ? Auth::user()->local_id
            : null;

        // Métodos de pago disponibles
        $metodosPago = ['efectivo','yape','plin','tarjeta','pedidosya','yape2'];

        // Fecha inicial
        $fechaActual = now()->format('Y-m-d');

        return Inertia::render('Ventas/Create', [
            'productos'    => $productos,
            'locales'      => $locales,
            'localAsignado'=> $local,
            'metodosPago'  => $metodosPago,
            'fechaActual'  => $fechaActual,
        ]);
    }
    
    // Guardar una nueva venta
    public function store(Request $request)
    {
        try {
            // Validación de los datos
            $validated = $request->validate([
                'productos.*.producto_id' => 'required|exists:productos_ventas,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                // Aceptamos tanto "on" como "si" para cortesía
                'productos.*.cortesia' => 'nullable|string|in:on,si',
                'local_id' => 'required|exists:locales,id',
                'pagos.*.metodo' => 'required|string|in:efectivo,yape,plin,tarjeta,pedidosya,yape2',
                'pagos.*.monto' => 'required|numeric|min:0',
                'fecha_venta' => 'required|date',
            ]);
    
            // Validar campos de delivery solo si es_delivery está marcado
            if ($request->has('es_delivery')) {
                $request->validate([
                    'nombre_cliente' => 'required|string|max:255',
                    'direccion_cliente' => 'required|string|max:255',
                    'numero_cliente' => 'required|string|max:255',
                ]);
            }
    
            // --- Calcular totales server-side ---
            $productosTotal = 0;
            $costoDeliveryCalculado = 0;
            foreach ($validated['productos'] as $productoData) {
                $productoVenta = ProductoVenta::find($productoData['producto_id']);
                $cantidad = $productoData['cantidad'];
    
                // Determinar si es cortesía: aceptamos "on" o "si" como activado
                $esCortesia = isset($productoData['cortesia']) && in_array($productoData['cortesia'], ['on', 'si']);
                $precioUnitario = $esCortesia ? 0 : $productoVenta->precio;
    
                // Sumar el precio de los productos
                $productosTotal += $precioUnitario * $cantidad;
    
                // Sumar el costo de delivery (por cada producto, según precio_delivery)
                $costoDeliveryCalculado += $productoVenta->precio_delivery * $cantidad;
            }
    
            // Si se envía un valor manual para el delivery (override), se usaría, 
            // pero en este ejemplo se elimina el override para forzar siempre el calculo.
            // $override = $request->has('costo_delivery') && floatval($request->costo_delivery) > 0;
            // $costoDeliveryFinal = $override ? floatval($request->costo_delivery) : $costoDeliveryCalculado;
            // En este caso, usamos el costoDeliveryCalculado siempre.
            $costoDeliveryFinal = $costoDeliveryCalculado;
    
            $isDelivery = $request->has('es_delivery');
            $totalFinal = $isDelivery ? $productosTotal + $costoDeliveryFinal : $productosTotal;
    
            // Crear la venta usando el total recalculado
            $venta = Venta::create([
                'user_id' => Auth::id(),
                'local_id' => $validated['local_id'],
                'total' => $totalFinal,
                'fecha_venta' => $validated['fecha_venta'],
                'es_delivery' => $isDelivery,
                'activo' => true,
                'metodo_pago' => $validated['pagos'][0]['metodo'],
            ]);
    
            // Guardar los detalles de los productos en detalle_venta
            foreach ($validated['productos'] as $productoData) {
                $productoVenta = ProductoVenta::find($productoData['producto_id']);
                $cantidad = $productoData['cantidad'];
                $esCortesia = isset($productoData['cortesia']) && in_array($productoData['cortesia'], ['on', 'si']);
                $precioUnitario = $esCortesia ? 0 : $productoVenta->precio;
    
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                ]);
            }
    
            // Registrar los métodos de pago
            foreach ($validated['pagos'] as $pago) {
                Pago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago' => $pago['metodo'],
                    'monto' => $pago['monto'],
                ]);
            }
    
            // Si es delivery, crear registro en Delivery y en DetalleDelivery con el costo calculado
            if ($isDelivery) {
                $delivery = Delivery::create([
                    'venta_id' => $venta->id,
                    'nombre_cliente' => $request->input('nombre_cliente'),
                    'direccion_cliente' => $request->input('direccion_cliente'),
                    'numero_cliente' => $request->input('numero_cliente'),
                    'user_id' => Auth::id(),
                    'hora_pedido' => now(),
                    'estado' => 'pendiente',
                    'metodo_pago' => $validated['pagos'][0]['metodo'],
                ]);
    
                DetalleDelivery::create([
                    'venta_id' => $venta->id,
                    'costo_delivery' => $costoDeliveryCalculado,
                ]);
    
                // Actualizar tiempo de entrega (si manejas esta lógica en DeliveryController)
                app(DeliveryController::class)->actualizarTiempoEntrega($delivery);
            }
    
            // Validar que la suma de los pagos coincida con el total final
            $totalPagos = array_sum(array_column($validated['pagos'], 'monto'));
            if (abs($totalPagos - $totalFinal) > 0.01) {
                return redirect()->back()->withErrors(['Los montos de los pagos no coinciden con el total recalculado.']);
            }
    
            return redirect()->route('ventas.index')
                ->with('success', 'Venta registrada con éxito.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['Error al registrar la venta: ' . $e->getMessage()]);
        }
    }
    
    // Mostrar el formulario para editar una venta
    public function edit(Venta $venta)
    {
        $productos = ProductoVenta::where('estado', 1)->get();
        $locales = Local::all();
        $metodosPago = ['efectivo', 'yape', 'plin', 'tarjeta', 'pedidosya', 'yape2'];
    
        // Cargar relaciones necesarias
        $venta->load('detalles', 'pagos', 'delivery', 'detalleDelivery');
    
        return Inertia::render('Ventas/Edit', compact(
            'venta', 'productos', 'locales', 'metodosPago'
          ));    
    }
    
    // Actualizar una venta existente
    public function update(Request $request, Venta $venta)
    {
        Log::info("Inicio del método update", ['venta_id' => $venta->id]);
    
        $validated = $request->validate([
            'productos.*.producto_id' => 'required|exists:productos_ventas,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'local_id' => 'required|exists:locales,id',
            'pagos.*.metodo' => 'required|string|in:efectivo,yape,plin,tarjeta,pedidosya,yape2',
            'pagos.*.monto' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);
    
        if ($request->has('es_delivery')) {
            $request->validate([
                'nombre_cliente' => 'required|string|max:255',
                'direccion_cliente' => 'required|string|max:255',
                'numero_cliente' => 'required|string|max:255',
            ]);
        } else {
            // Si no es delivery, eliminar registros de delivery
            Delivery::where('venta_id', $venta->id)->delete();
            DetalleDelivery::where('venta_id', $venta->id)->delete();
        }
    
        Log::info("Datos validados correctamente", ['validated_data' => $validated]);
    
        // Recalcular total y costo de delivery server-side
        $productosTotal = 0;
        $costoDeliveryCalculado = 0;
    
        // Eliminar detalles anteriores
        DetalleVenta::where('venta_id', $venta->id)->delete();
    
        foreach ($validated['productos'] as $productoData) {
            $producto = ProductoVenta::findOrFail($productoData['producto_id']);
            $cantidad = $productoData['cantidad'];
            $precioUnitario = $producto->precio;
    
            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
            ]);
    
            $productosTotal += $cantidad * $precioUnitario;
            $costoDeliveryCalculado += $producto->precio_delivery * $cantidad;
        }
    
        $isDelivery = $request->has('es_delivery');
        $nuevoTotal = $isDelivery ? $productosTotal + $costoDeliveryCalculado : $productosTotal;
    
        Log::info("Nuevo total calculado después de productos", ['nuevo_total' => $nuevoTotal]);
    
        // Actualizar los métodos de pago
        Pago::where('venta_id', $venta->id)->delete();
        foreach ($validated['pagos'] as $pago) {
            Pago::create([
                'venta_id' => $venta->id,
                'metodo_pago' => $pago['metodo'],
                'monto' => $pago['monto'],
            ]);
        }
    
        Log::info("Métodos de pago actualizados", ['venta_id' => $venta->id]);
    
        $totalPagos = array_sum(array_column($validated['pagos'], 'monto'));
        Log::info("Validación de pagos", ['total_pagos' => $totalPagos, 'nuevo_total' => $nuevoTotal]);
    
        if (abs($totalPagos - $nuevoTotal) > 0.01) {
            Log::error("El monto pagado no coincide con el total de la venta", [
                'total_pagos' => $totalPagos,
                'nuevo_total' => $nuevoTotal,
            ]);
            return redirect()->back()->withErrors(['El monto pagado no coincide con el total de la venta.']);
        }
    
        // Actualizar la venta
        $venta->update([
            'total' => $nuevoTotal,
            'metodo_pago' => $validated['pagos'][0]['metodo'],
            'es_delivery' => $isDelivery,
            'local_id' => $validated['local_id'],
            'activo' => true,
        ]);
    
        // Gestión de Delivery
        if ($isDelivery) {
            Delivery::updateOrCreate(
                ['venta_id' => $venta->id],
                [
                    'nombre_cliente' => $request->input('nombre_cliente'),
                    'direccion_cliente' => $request->input('direccion_cliente'),
                    'numero_cliente' => $request->input('numero_cliente'),
                    'user_id' => Auth::id(),
                    'hora_pedido' => now(),
                    'estado' => 'pendiente',
                    'metodo_pago' => $validated['pagos'][0]['metodo'],
                ]
            );
    
            DetalleDelivery::updateOrCreate(
                ['venta_id' => $venta->id],
                ['costo_delivery' => $costoDeliveryCalculado]
            );
        }
    
        Log::info("Venta actualizada con éxito", ['venta_id' => $venta->id]);
    
        return redirect()->route('ventas.index')->with('success', 'Venta actualizada con éxito.');
    }
    
    // Cambiar estado a inactivo en lugar de eliminar
    public function destroy(Venta $venta)
    {
        $venta->update(['activo' => !$venta->activo]);
        $mensaje = $venta->activo ? 'Venta activada.' : 'Venta desactivada.';
        return redirect()->route('ventas.index')->with('success', $mensaje);
    }
    
    // Método para obtener saldos por métodos de pago, incluyendo PedidosYA
    public function getSaldos(Request $request)
    {
        try {
            $fecha = $request->input('fecha_venta') ?? now()->format('Y-m-d');
            $localId = $request->input('local_id');
            $esDelivery = $request->input('es_delivery');
    
            Log::info('Parámetros recibidos:', compact('fecha', 'localId', 'esDelivery'));
    
            $query = Venta::whereDate('fecha_venta', $fecha)->where('activo', true);
            if ($localId) {
                $query->where('local_id', $localId);
            }
            if ($esDelivery !== null) {
                $query->where('es_delivery', $esDelivery);
            }
    
            $ventas = $query->get();
    
            Log::info('Ventas recuperadas:', $ventas->toArray());
    
            $pagos = Pago::whereIn('venta_id', $ventas->pluck('id'))->get();
    
            Log::info('Pagos recuperados:', $pagos->toArray());
    
            $saldos = [
                'total' => $pagos->sum('monto'),
                'efectivo' => $pagos->where('metodo_pago', 'efectivo')->sum('monto'),
                'yape' => $pagos->where('metodo_pago', 'yape')->sum('monto'),
                'plin' => $pagos->where('metodo_pago', 'plin')->sum('monto'),
                'tarjeta' => $pagos->where('metodo_pago', 'tarjeta')->sum('monto'),
                'pedidosya' => $pagos->where('metodo_pago', 'pedidosya')->sum('monto'),
                'yape2' => $pagos->where('metodo_pago', 'yape2')->sum('monto'),
            ];
    
            Log::info('Saldos calculados:', $saldos);
    
            return response()->json($saldos);
        } catch (\Exception $e) {
            Log::error('Error en getSaldos:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al consultar los saldos: ' . $e->getMessage()], 500);
        }
    }
    
    // Método para obtener el total de "pollos vendidos" usando la columna "equivalente_pollos"
    public function getPollosVendidos(Request $request)
    {
        try {
            $fecha = $request->input('fecha_venta') ?? now()->format('Y-m-d');
            $localId = $request->input('local_id');
    
            $query = Venta::whereDate('fecha_venta', $fecha)
                          ->where('activo', true);
    
            if ($localId) {
                Log::info("Filtrando por local ID: $localId");
                $query->where('local_id', $localId);
            }
    
            // Cargar ventas con detalles y producto
            $ventas = $query->with('detalles.producto')->get();
    
            $totalPollosVendidos = 0;
            foreach ($ventas as $venta) {
                foreach ($venta->detalles as $detalle) {
                    $cantidad = $detalle->cantidad;
                    // Usar el campo "equivalente_pollos" del producto
                    $equivalente = $detalle->producto->equivalente_pollos ?? 1;
                    $totalPollosVendidos += $cantidad * $equivalente;
                }
            }
    
            Log::info('Total pollos vendidos calculado: ' . $totalPollosVendidos);
    
            return response()->json([
                'totalPollosVendidos' => $totalPollosVendidos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al consultar los pollos vendidos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar los pollos vendidos: ' . $e->getMessage()], 500);
        }
    }

    public function infoProductos()
{
    // Obtenemos todos los productos activos con nombre y descripcion
    $productos = ProductoVenta::select('nombre', 'descripcion')
                              ->where('estado', 1)
                              ->get();

    // Retornamos la vista infomodal con la lista de productos
    return view('ventas.infomodal', compact('productos'));
}

}
