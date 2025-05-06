<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DetalleVenta;
use App\Models\DetalleDelivery;
use App\Models\InsumosPorProducto;
use App\Models\InventarioLocal;
use App\Models\Local;
use App\Models\Pago;
use App\Models\ProductoVenta;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    // Mostrar la lista de deliveries con filtros
    public function index(Request $request)
    {
        $locales = Local::all();
        $query = Delivery::query()->with('venta');
    
        // Obtener la fecha actual
        $fechaActual = now()->format('Y-m-d');
    
        // Si no se proporciona una fecha, usar la fecha actual
        if (!$request->filled('fecha')) {
            $query->whereDate('created_at', $fechaActual);
        } else {
            $query->whereDate('created_at', $request->fecha);
        }
    
        // Filtrar por local si se selecciona uno
        if ($request->filled('local_id')) {
            $query->whereHas('venta', function($q) use ($request) {
                $q->where('local_id', $request->local_id);
            });
        }
          // Ordenar los deliveries por fecha de creación, de más reciente a más antiguo
        $deliveries = $query->orderBy('created_at', 'desc')->get();
    
        $deliveries = $query->get();
        return view('deliveries.index', compact('deliveries', 'locales', 'fechaActual'));
    }
    

    // Mostrar el formulario para crear un nuevo delivery
    public function create()
    {
        $productos = ProductoVenta::where('estado', 1)->get();
        $locales = Local::all();
    
        // Obtener el rol del usuario autenticado
        $roleId = DB::table('model_has_roles')
            ->where('model_id', Auth::user()->id)
            ->value('role_id');
    
        $user = Auth::user();
        $local = $user->local_id; // Local del usuario autenticado
    
        // Si el rol es "dueño" o "admin", permitir que cambien de local
        if ($roleId == 1 || $roleId == 2) { // 1 = dueño, 2 = admin
            return view('deliveries.create', compact('productos', 'locales', 'local'));
        }
        // Si es "cajera", asignar automáticamente el local
        elseif ($roleId == 3) { // 3 = cajera
            return view('deliveries.create', compact('productos', 'local'));
        }
        // Redirigir si no tiene permisos
        else {
            return redirect()->route('home')->with('error', 'No tienes permisos para crear un delivery.');
        }
    }
    
    
    

    // Guardar un nuevo delivery con múltiples productos
    public function store(Request $request)
    {
        DB::beginTransaction(); // Inicia la transacción
        try {
            // Validar los datos del formulario
            $validated = $request->validate([
                'productos.*.producto_id' => 'required|exists:productos_ventas,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'nombre_cliente' => 'required|string|max:255',
                'direccion_cliente' => 'required|string|max:255',
                'numero_cliente' => 'required|digits:9',
                'local_id' => 'required|exists:locales,id',
                'pagos.*.metodo' => 'required|string|in:efectivo,yape,tarjeta,pedidosya',
                'pagos.*.monto' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'costo_delivery' => 'nullable|numeric|min:0',
            ]);
    
            Log::info('Datos validados correctamente', ['validated' => $validated]);
    
            // Validar que la suma de los pagos coincida con el total
            $totalPagos = array_sum(array_column($validated['pagos'], 'monto'));
            if ($totalPagos != $validated['total']) {
                Log::error('Los montos de los pagos no coinciden con el total', [
                    'totalPagos' => $totalPagos,
                    'total' => $validated['total'],
                ]);
                return redirect()->back()->withErrors(['error' => 'Los montos de los pagos no coinciden con el total.']);
            }
    
            // Crear la venta
            $venta = Venta::create([
                'user_id' => Auth::id(),
                'local_id' => $validated['local_id'],
                'total' => $validated['total'],
                'fecha_venta' => now(),
                'es_delivery' => true,
                'activo' => true,
                'metodo_pago' => $validated['pagos'][0]['metodo'], // Primer método como referencia
            ]);
            Log::info('Venta creada correctamente', ['venta_id' => $venta->id]);
    
            // Guardar los detalles de la venta
            foreach ($validated['productos'] as $productoData) {
                $productoVenta = ProductoVenta::findOrFail($productoData['producto_id']);
    
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $productoVenta->precio,
                ]);
                Log::info('Detalle de venta creado', [
                    'venta_id' => $venta->id,
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $productoData['cantidad'],
                ]);
            }
    
            // Registrar los métodos de pago
            foreach ($validated['pagos'] as $pago) {
                Pago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago' => $pago['metodo'],
                    'monto' => $pago['monto'],
                ]);
                Log::info('Pago registrado', [
                    'venta_id' => $venta->id,
                    'metodo_pago' => $pago['metodo'],
                    'monto' => $pago['monto'],
                ]);
            }
    
            // Crear el delivery
            $delivery = Delivery::create([
                'venta_id' => $venta->id,
                'nombre_cliente' => $validated['nombre_cliente'],
                'direccion_cliente' => $validated['direccion_cliente'],
                'numero_cliente' => $validated['numero_cliente'],
                'costo_delivery' => $validated['costo_delivery'],
                'user_id' => Auth::id(),
                'hora_pedido' => now(), // Hora actual como predeterminada
                'estado' => 'pendiente',
                'metodo_pago' => $validated['pagos'][0]['metodo'], // Asignar el primer método de pago
            ]);
            Log::info('Delivery creado correctamente', ['delivery_id' => $delivery->id]);
    
            // Crear el detalle del delivery
            DetalleDelivery::create([
                'venta_id' => $venta->id,
                'costo_delivery' => $validated['costo_delivery'],
            ]);
            Log::info('Detalle de delivery creado', ['venta_id' => $venta->id]);
    
            DB::commit(); // Confirma la transacción
            return redirect()->route('deliveries.index')->with('success', 'Delivery creado con éxito.');
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte los cambios
            Log::error('Error al crear delivery: ' . $e->getMessage(), [
                'data' => $request->all(),
            ]);
            return back()->withErrors(['error' => 'Ocurrió un error al crear el delivery: ' . $e->getMessage()]);
        }
    }
    
    
    
    /*
    private function reducirInventarioPorVenta($productoVenta, $cantidadVendida, $localId)
{
    // Obtener los insumos asociados a este producto de venta
    $insumos = InsumosPorProducto::where('producto_venta_id', $productoVenta->id)->get();

    foreach ($insumos as $insumo) {
        // Calcular la cantidad de insumo a consumir según la cantidad vendida
        $cantidadInsumoConsumida = $insumo->cantidad * $cantidadVendida;

        // Buscar el inventario del local específico para este insumo
        $inventarioLocal = InventarioLocal::where('producto_almacen_id', $insumo->producto_almacen_id)
                            ->where('local_id', $localId)
                            ->first();

        if ($inventarioLocal) {
            // Actualizar la cantidad del inventario local restando lo consumido
            $inventarioLocal->cantidad -= $cantidadInsumoConsumida;

            // Verificar si la cantidad es negativa, generar una alerta o manejarlo
            if ($inventarioLocal->cantidad < 0) {
                return redirect()->back()->withErrors(['error' => 'Inventario insuficiente para el insumo: ' . $insumo->productoAlmacen->nombre]);
            }

            $inventarioLocal->save();
        } else {
            return redirect()->back()->withErrors(['error' => 'No hay inventario disponible para el insumo: ' . $insumo->productoAlmacen->nombre]);
        }
    }
}
*/
    
    // Mostrar el formulario para editar un delivery
    public function edit($id)
    {
        // Encontrar el delivery a editar
        $delivery = Delivery::findOrFail($id);
    
        // Obtener todos los productos disponibles
        $productos = ProductoVenta::where('estado', 1)->get();
    
        // Obtener los pagos asociados
        $pagos = $delivery->venta->pagos;
    
        // Enviar los datos a la vista de edición
        return view('deliveries.edit', compact('delivery', 'productos', 'pagos'));
    }
    

    public function update(Request $request, $id)
    {
        try {
            Log::info('Iniciando actualización de Delivery', ['delivery_id' => $id, 'request_data' => $request->all()]);
    
            $delivery = Delivery::findOrFail($id);
            $venta = $delivery->venta;
    
            $validated = $request->validate([
                'productos.*.producto_id' => 'required|exists:productos_ventas,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'nombre_cliente' => 'required|string|max:255',
                'direccion_cliente' => 'required|string|max:255',
                'numero_cliente' => 'required|digits:9',
                'pagos.*.metodo' => 'required|string|in:efectivo,yape,tarjeta,pedidosya',
                'pagos.*.monto' => 'required|numeric|min:0',
                'costo_delivery' => 'required|numeric|min:0',
                'total' => 'required|numeric',
                'estado' => 'required|in:pendiente,entregado,cancelado',
                'local_id' => 'exists:locales,id'
            ]);
            Log::info('Datos validados en update', ['validated' => $validated]);
    
            $totalPagos = array_sum(array_column($validated['pagos'], 'monto'));
            Log::info('Suma de pagos calculada en update', ['totalPagos' => $totalPagos, 'total' => $validated['total']]);
            
            if ($totalPagos != $validated['total']) {
                Log::warning('Los montos de pago no coinciden con el total en update', ['totalPagos' => $totalPagos, 'total' => $validated['total']]);
                return back()->withErrors(['error' => 'Los montos de los pagos no coinciden con el total.']);
            }
    
            // Actualizar datos del delivery
            $delivery->update([
                'nombre_cliente' => $validated['nombre_cliente'],
                'direccion_cliente' => $validated['direccion_cliente'],
                'numero_cliente' => $validated['numero_cliente'],
                'estado' => $validated['estado'],
                'costo_delivery' => $validated['costo_delivery'],
            ]);
            Log::info('Delivery actualizado', ['delivery_id' => $delivery->id]);
    
            // Actualizar la venta
            $venta->update([
                'total' => $validated['total'],
                'local_id' => $validated['local_id'] ?? $venta->local_id,
            ]);
            Log::info('Venta actualizada', ['venta_id' => $venta->id]);
    
            // Actualizar métodos de pago
            Pago::where('venta_id', $venta->id)->delete();
            foreach ($validated['pagos'] as $pagoData) {
                Log::info('Re-registrando pago en update', $pagoData);
                Pago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago' => $pagoData['metodo'],
                    'monto' => $pagoData['monto'],
                ]);
            }
    
            // Actualizar detalles de venta
            DetalleVenta::where('venta_id', $venta->id)->delete();
            foreach ($validated['productos'] as $prodData) {
                Log::info('Actualizando detalle de venta', $prodData);
                $productoVenta = ProductoVenta::findOrFail($prodData['producto_id']);
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $prodData['producto_id'],
                    'cantidad' => $prodData['cantidad'],
                    'precio_unitario' => $productoVenta->precio,
                ]);
            }
    
            Log::info('Delivery actualizado correctamente', ['delivery_id' => $delivery->id]);
            return redirect()->route('deliveries.index')->with('success', 'Delivery actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar delivery', ['exception' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Ocurrió un error al actualizar el delivery: ' . $e->getMessage()]);
        }
    }
    
    
    public function updateEstado(Request $request, $id)
    {
        $delivery = Delivery::findOrFail($id);

        // Validar estado
        $request->validate([
            'estado' => 'required|in:pendiente,entregado,cancelado'
        ]);

        $delivery->estado = $request->estado;

        // Actualizar hora_entrega y tiempo_demora si el estado es entregado
        $delivery->save();
        $this->actualizarTiempoEntrega($delivery);

        return redirect()->route('deliveries.index')->with('success', 'Estado del Delivery actualizado correctamente.');
    }

    
    public function actualizarTiempoEntrega(Delivery $delivery)
    {
        // Registrar la hora de entrega si el estado es "entregado"
        if ($delivery->estado === 'entregado' && is_null($delivery->hora_entrega)) {
            $delivery->hora_entrega = now();  // Solo asignar la hora de entrega si no está previamente asignada
        }

        // Calcular el tiempo de demora si hay hora_pedido y hora_entrega
        if ($delivery->hora_pedido && $delivery->hora_entrega) {
            $tiempoDemora = \Carbon\Carbon::parse($delivery->hora_pedido)->diffInMinutes($delivery->hora_entrega);
            $delivery->tiempo_demora = $tiempoDemora;
            $delivery->save(); // Guardar el tiempo de demora actualizado
        }

        return $delivery;
    }

    
    
    
    
    
    
}
