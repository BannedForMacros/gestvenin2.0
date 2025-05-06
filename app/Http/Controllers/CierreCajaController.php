<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\DetalleCierreCaja;
use App\Models\DiscrepanciaInventarioLocal;
use App\Models\Venta;
use App\Models\GastoVenta;
use App\Models\InsumosPorProducto;
use App\Models\InventarioLocal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MetodoPagoCierreCaja;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;


class CierreCajaController extends Controller
{
    public function index()
    {
        $cierres = CierreCaja::with('local') // Asegúrate de tener la relación 'local' en el modelo
            ->orderBy('fecha_cierre', 'desc')
            ->get();
    
        return view('cierres.index', compact('cierres'));
    }
    public function create(Request $request)
    {
        $fecha = $request->input('fecha_venta') ?? now()->format('Y-m-d');
        $localId = $request->input('local_id');
    
        if (Auth::user()->hasRole('cajera')) {
            $localId = Auth::user()->local_id;
        }
    
        if (!$localId) {
            return redirect()->back()->withErrors(['error' => 'Debe seleccionar un local para cerrar la caja.']);
        }
    
        $localNombre = DB::table('locales')->where('id', $localId)->value('nombre_local');
    
        // Consultar ventas con pagos y detalles
        $ventas = Venta::whereDate('fecha_venta', $fecha)
            ->where('local_id', $localId)
            ->where('activo', true)
            ->with(['detalles.producto', 'pagos'])
            ->get();
    
        // Agrupar detalles por producto
        $detallesAgrupados = [];
        foreach ($ventas as $venta) {
            foreach ($venta->detalles as $detalle) {
                $productoNombre = $detalle->producto->nombre;
                $precioUnitario = $detalle->precio_unitario;
                $subtotal = $detalle->cantidad * $precioUnitario;
    
                if (!isset($detallesAgrupados[$productoNombre])) {
                    $detallesAgrupados[$productoNombre] = [
                        'producto' => $productoNombre,
                        'cantidad' => 0,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => 0,
                    ];
                }
    
                $detallesAgrupados[$productoNombre]['cantidad'] += $detalle->cantidad;
                $detallesAgrupados[$productoNombre]['subtotal'] += $subtotal;
            }
        }
    
        // Consultar y sumar costos de delivery y cantidad de ventas con delivery
        $detalleDelivery = DB::table('detalle_delivery')
            ->whereIn('venta_id', $ventas->pluck('id'))
            ->get();
    
        $costoDeliveryTotal = $detalleDelivery->sum('costo_delivery');
        $cantidadDeliveries = $detalleDelivery->count(); // Cantidad de ventas con delivery
    
        // Agregar el "Costo de Delivery" si hay deliveries
        if ($costoDeliveryTotal > 0) {
            $detallesAgrupados['Costo de Delivery'] = [
                'producto' => 'Costo de Delivery',
                'cantidad' => $cantidadDeliveries, // Cantidad de ventas con delivery
                'precio_unitario' => '--', // No aplica un precio unitario
                'subtotal' => $costoDeliveryTotal,
            ];
        }
    
        // Calcular totales por método de pago dinámico
        $totalesPagos = [
            'efectivo' => 0,
            'yape' => 0,
            'plin' =>0,
            'tarjeta' => 0,
            'pedidosya' => 0,
            'yape2' => 0,
        ];
    
        foreach ($ventas as $venta) {
            foreach ($venta->pagos as $pago) {
                $metodo = strtolower($pago->metodo_pago); // Convertir a minúsculas para evitar discrepancias
                $monto = $pago->monto;
    
                if (isset($totalesPagos[$metodo])) {
                    $totalesPagos[$metodo] += $monto;
                } else {
                    $totalesPagos[$metodo] = $monto; // Agregar método desconocido si aplica
                }
            }
        }
    
        $totalVentas = array_sum($totalesPagos);
    
        // Consultar gastos del día
        $gastos = GastoVenta::whereDate('fecha_gasto', $fecha)
            ->where('local_id', $localId)
            ->where('activo', true)
            ->sum('monto');
    
        $balanceFinal = $totalVentas - $gastos;
    
        return view('cierres.create', [
            'detallesAgrupados' => $detallesAgrupados,
            'totales' => array_merge($totalesPagos, ['total' => $totalVentas]),
            'gastos' => $gastos,
            'balanceFinal' => $balanceFinal,
            'fecha' => $fecha,
            'localId' => $localId,
            'localNombre' => $localNombre,
        ]);
    }
    
    
    

    public function store(Request $request)
    {
        try {
            // Validación de los datos enviados
            $validatedData = $request->validate([
                'local_id' => 'required|exists:locales,id',
                'fecha_cierre' => 'required|date',
                'total_ventas' => 'required|numeric',
                'total_gastos' => 'required|numeric',
                'balance_final' => 'required|numeric',
                'detalles' => 'required|array',
                'detalles.*.producto' => 'required|string',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.precio_unitario' => 'required|numeric',
                'detalles.*.subtotal' => 'required|numeric',
                'metodos_pago' => 'required|array',
                'metodos_pago.*.metodo' => 'required|string',
                'metodos_pago.*.monto' => 'required|numeric',
            ]);
    
            DB::beginTransaction();
    
            // Buscar un cierre existente con la misma fecha y local
            $cierreExistente = CierreCaja::where('local_id', $validatedData['local_id'])
                ->whereDate('fecha_cierre', $validatedData['fecha_cierre'])
                ->first();
    
            if ($cierreExistente) {
                // Eliminar detalles y métodos de pago asociados
                DetalleCierreCaja::where('cierre_caja_id', $cierreExistente->id)->delete();
                MetodoPagoCierreCaja::where('cierre_caja_id', $cierreExistente->id)->delete();
    
                // Eliminar el cierre existente
                $cierreExistente->delete();
            }
    
            // Crear un nuevo cierre de caja
            $cierreCaja = CierreCaja::create([
                'local_id' => $validatedData['local_id'],
                'fecha_cierre' => $validatedData['fecha_cierre'],
                'total_ventas' => $validatedData['total_ventas'],
                'total_gastos' => $validatedData['total_gastos'],
                'balance_final' => $validatedData['balance_final'],
                'total_pollos_vendidos' => $this->calcularPollosVendidos($validatedData['detalles']),
            ]);
    
            // Registrar los detalles del cierre
            foreach ($validatedData['detalles'] as $detalle) {
                DetalleCierreCaja::create([
                    'cierre_caja_id' => $cierreCaja->id,
                    'producto' => $detalle['producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['subtotal'],
                ]);
            }
    
            // Registrar los métodos de pago
            foreach ($validatedData['metodos_pago'] as $metodoPago) {
                MetodoPagoCierreCaja::create([
                    'cierre_caja_id' => $cierreCaja->id,
                    'metodo' => $metodoPago['metodo'],
                    'monto' => $metodoPago['monto'],
                ]);
            }
    
            // Actualizar las discrepancias
            $this->actualizarDiscrepancias($validatedData['local_id'], $validatedData['fecha_cierre']);
    
            DB::commit();
    
            // Responder con JSON si es una solicitud AJAX
            if ($request->ajax()) {
                $redirectUrl = Auth::user()->hasRole('cajera') ? route('dashboard') : route('cierres.index');
                return response()->json([
                    'success' => 'Cierre de caja realizado exitosamente.',
                    'redirect_url' => $redirectUrl,
                ]);
            }
    
            // Redirigir según el rol del usuario
            if (Auth::user()->hasRole('cajera')) {
                return redirect()->route('dashboard')->with('success', 'Cierre de caja realizado exitosamente.');
            }
    
            return redirect()->route('cierres.index')->with('success', 'Cierre de caja actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
    
            Log::error('Error al procesar el cierre de caja: ' . $e->getMessage());
    
            // Responder con JSON si es una solicitud AJAX
            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Hubo un error al procesar el cierre de caja.',
                ], 500);
            }
    
            // Redirigir con errores si no es una solicitud AJAX
            return redirect()->back()->withErrors(['error' => 'Hubo un error al procesar el cierre de caja: ' . $e->getMessage()]);
        }
    }
    
    

    private function calcularPollosVendidos($detalles)
    {
        $totalPollos = 0;
        foreach ($detalles as $detalle) {
            $producto = $detalle['producto'];
            $cantidad = $detalle['cantidad'];

            if ($producto == 'Pollo Completo') {
                $totalPollos += $cantidad;
            } elseif ($producto == 'Promoción') {
                $totalPollos += $cantidad * 1.25;
            } elseif (in_array($producto, ['Medio Pollo Completo', 'Medio Pollo Solo'])) {
                $totalPollos += $cantidad * 0.5;
            } elseif ($producto == 'Pollo Solo') {
                $totalPollos += $cantidad;
            } elseif ($producto == 'Cuarto De Pollo Completo') {
                $totalPollos += $cantidad * 0.25;
            } elseif ($producto == 'Pollo Cena') {
                $totalPollos += $cantidad;
            } elseif ($producto == 'Medio Pollo Cena') {
                $totalPollos += $cantidad * 0.5;
            } elseif ($producto == 'Cuarto Chaufa'){
            $totalPollos += $cantidad * 0.25;
            } elseif ($producto == 'Promocion 2'){
            $totalPollos += $cantidad * 1.25;
            } elseif ($producto == 'Promoción 3'){
                $totalPollos += $cantidad * 1;
            } elseif ($producto == 'Pollo Descarte'){
            $totalPollos += $cantidad * 0.25;
            } 
        }
        return round($totalPollos, 2);
    }
    public function show($id)
    {
        $cierreCaja = CierreCaja::with(['detalles']) // Asegúrate de tener la relación 'detalles' en el modelo
            ->where('id', $id)
            ->firstOrFail();

        return view('cierres.show', compact('cierreCaja'));
    }
    
        
    public function audit($id)
    {
        $cierreCaja = CierreCaja::with(['detalles', 'metodosPago', 'local'])->findOrFail($id);

        // Consultar discrepancias basadas en la fecha del cierre
        $discrepancias = DiscrepanciaInventarioLocal::whereDate('fecha', $cierreCaja->fecha_cierre)
            ->where('local_id', $cierreCaja->local_id)
            ->get();

        return view('cierres.audit', compact('cierreCaja', 'discrepancias'));
    }

    private function actualizarDiscrepancias($localId, $fecha)
    {
        // Obtener los productos relacionados con InsumosPorProducto
        $productosRelacionados = InsumosPorProducto::distinct()->pluck('producto_almacen_id')->toArray();
    
        // Filtrar inventarios solo de productos relacionados
        $inventarios = InventarioLocal::where('local_id', $localId)
            ->whereIn('producto_almacen_id', $productosRelacionados)
            ->get();
    
        foreach ($inventarios as $inventario) {
            $productoAlmacenId = $inventario->producto_almacen_id;
    
            // Obtener la discrepancia existente
            $discrepanciaExistente = DB::table('discrepancia_inventario_local')
                ->where('local_id', $localId)
                ->where('producto_almacen_id', $productoAlmacenId)
                ->whereDate('fecha', $fecha)
                ->first();
    
            $consumoReal = $discrepanciaExistente->consumo_real ?? 0;
    
            // Calcular el consumo teórico basado en ventas activas
            $insumos = InsumosPorProducto::where('producto_almacen_id', $productoAlmacenId)->get();
            $consumoTeorico = 0;
    
            foreach ($insumos as $insumo) {
                $ventasCantidad = DB::table('detalle_venta')
                    ->join('ventas', 'detalle_venta.venta_id', '=', 'ventas.id')
                    ->where('detalle_venta.producto_id', $insumo->producto_venta_id)
                    ->where('ventas.local_id', $localId)
                    ->whereDate('ventas.fecha_venta', $fecha)
                    ->where('ventas.activo', true)
                    ->sum('detalle_venta.cantidad');
    
                $consumoTeorico += $ventasCantidad * $insumo->cantidad;
            }
    
            // Calcular la diferencia
            $diferencia = $consumoTeorico - $consumoReal;
    
            // Registrar o actualizar la discrepancia
            DB::table('discrepancia_inventario_local')
                ->updateOrInsert(
                    [
                        'local_id' => $localId,
                        'producto_almacen_id' => $productoAlmacenId,
                        'fecha' => $fecha,
                    ],
                    [
                        'consumo_teorico' => $consumoTeorico,
                        'consumo_real' => $consumoReal,
                        'diferencia' => $diferencia,
                        'updated_at' => now(),
                    ]
                );
        }
    }
    
    
    
    

    
}

