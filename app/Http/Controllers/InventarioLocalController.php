<?php

namespace App\Http\Controllers;

use App\Models\DetalleHistorialInventarioLocal;
use App\Models\InventarioLocal;
use App\Models\HistorialInventarioLocal;
use App\Models\EntradasLocal;
use App\Models\Local;
use App\Models\Categoria;
use App\Models\ProductoAlmacen;
use App\Models\InsumosPorProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class InventarioLocalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Cargamos todas las categorías para el filtro:
        $categorias = Categoria::all();

        // Roles y locales como antes...
        $userRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->value('roles.name');

        $locales = [];
        $localSeleccionado = null;
        $stockGuardado = false;

        if ($userRole === 'dueño' || $userRole === 'admin') {
            $locales = Local::all();
            if ($request->filled('local_id')) {
                $localSeleccionado = Local::find($request->input('local_id'));
            }
            $query = InventarioLocal::with(['productoAlmacen.unidadMedida', 'productoAlmacen.categoria', 'local'])
                      ->when($request->filled('local_id'), fn($q) => $q->where('local_id', $request->input('local_id')));
        } else {
            // Cajera / cremas
            $localSeleccionado = Local::find($user->local_id);
            $stockGuardado = HistorialInventarioLocal::where('local_id', $user->local_id)
                             ->whereDate('fecha', now())
                             ->exists();
            $query = InventarioLocal::with(['productoAlmacen.unidadMedida', 'productoAlmacen.categoria', 'local'])
                      ->where('local_id', $user->local_id);
        }

        $inventarios = $query->get();

        return Inertia::render('InventarioLocal/Index', [
            'inventarios'        => $inventarios,
            'locales'            => $locales,
            'localSeleccionado'  => $localSeleccionado,
            'stockGuardado'      => $stockGuardado,
            'categorias'         => $categorias,
            'auth'               => [
                'roles'    => $user->roles->pluck('name')->toArray(),
                'user'     => ['local_id' => $user->local_id],
            ],
        ]);
    }
    

    public function registrarStockFinal(Request $request, $localId)
    {
        $local  = Local::findOrFail($localId);
        $fecha  = $request->input('fecha', Carbon::now()->format('Y-m-d'));

        // 1) historial de ayer
        $historialAnt = HistorialInventarioLocal::where('local_id', $localId)
            ->where('fecha', Carbon::parse($fecha)->subDay()->format('Y-m-d'))
            ->first();

        $inventarios = InventarioLocal::with(['productoAlmacen.unidadMedida','productoAlmacen.categoria'])
            ->where('local_id', $localId)
            ->get()
            ->map(function ($inv) use ($localId, $fecha, $historialAnt) {
                $stockAyer = 0;
                if ($historialAnt) {
                    $detalleAnt = $historialAnt->detalles()
                        ->where('producto_almacen_id', $inv->producto_almacen_id)
                        ->first();
                    $stockAyer = $detalleAnt->stock_final ?? 0;
                }

                // 2) sumar entradas del día
                $entradasHoy = EntradasLocal::where('local_id', $localId)
                    ->whereDate('fecha_entrada', $fecha)
                    ->join('detalle_entradas_local as d', 'd.entrada_local_id', 'entradas_local.id')
                    ->where('d.producto_almacen_id', $inv->producto_almacen_id)
                    ->sum('d.cantidad_entrada');

                return [
                    'producto_almacen_id' => $inv->producto_almacen_id,
                    'producto'           => $inv->productoAlmacen->nombre,
                    'unidad'             => $inv->productoAlmacen->unidadMedida->nombre,
                    'categoria'          => $inv->productoAlmacen->categoria->nombre,
                    'stock_inicial'      => $stockAyer + $entradasHoy,
                    'entradas'           => $entradasHoy,
                    'stock_final'        => $historialAnt
                                               ? ($historialAnt->detalles()
                                                   ->where('producto_almacen_id', $inv->producto_almacen_id)
                                                   ->first()
                                                   ->stock_final ?? 0)
                                               : $inv->cantidad,
                ];
            });

        return Inertia::render('InventarioLocal/StockFinalRegistro', [
            'local'         => $local,
            'fecha'         => $fecha,
            'inventarios'   => $inventarios,
            'stockGuardado' => HistorialInventarioLocal::where('local_id',$localId)
                                  ->where('fecha',$fecha)->exists(),
            'auth'          => [
                'roles' => Auth::user()->roles->pluck('name')->toArray(),
                'user'  => Auth::user(),
            ],
        ]);
    }
    
    

    public function guardarStockFinal(Request $request, $localId)
    {
        $fecha = $request->input('fecha'); // Fecha seleccionada por el usuario
    
        if (!$fecha) {
            return redirect()->back()->withErrors('Debe seleccionar una fecha válida.');
        }
    
        // Validar los datos
        $validated = $request->validate([
            'stocks' => 'required|array',
            'stocks.*' => 'required|numeric|min:0', // Validar stock final
            'stocks_iniciales' => 'required|array',
            'stocks_iniciales.*' => 'required|numeric|min:0', // Validar stock inicial
        ]);
    
        $totalProductos = 0;
    
        // Crear o actualizar el historial general para este local
        $historialLocal = HistorialInventarioLocal::updateOrCreate(
            [
                'local_id' => $localId,
                'fecha' => $fecha,
            ],
            [
                'total_productos' => 0, // Se actualizará después
            ]
        );
    
        foreach ($validated['stocks'] as $productoAlmacenId => $cantidadIngresada) {
            $stockInicial = $validated['stocks_iniciales'][$productoAlmacenId] ?? 0;
    
            // Buscar inventario local relacionado
            $inventario = InventarioLocal::where('producto_almacen_id', $productoAlmacenId)
                ->where('local_id', $localId)
                ->first();
    
            if ($inventario) {
                // Actualizar el inventario con el stock final ingresado
                $inventario->cantidad = $cantidadIngresada;
                $inventario->save();
            }
    
            // Crear o actualizar el detalle del historial
            DetalleHistorialInventarioLocal::updateOrCreate(
                [
                    'historial_inventario_local_id' => $historialLocal->id,
                    'producto_almacen_id' => $productoAlmacenId,
                ],
                [
                    'stock_inicial' => $stockInicial,
                    'stock_final' => $cantidadIngresada,
                ]
            );
    
            $totalProductos++;
    
            // Procesar discrepancias
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
    
            $consumoReal = $stockInicial - $cantidadIngresada;
            $diferencia = $consumoTeorico - $consumoReal;
    
            // Registrar o actualizar la discrepancia
            DB::table('discrepancia_inventario_local')->updateOrInsert(
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
    
        // Actualizar el total de productos en el historial
        $historialLocal->update(['total_productos' => $totalProductos]);
    
        return redirect()->route('inventario_local.index')
            ->with('success', 'El stock final ha sido registrado correctamente.');
    }
    
    
    // app/Http/Controllers/InventarioLocalController.php

public function apiStockActual($localId)
{
    $stocks = InventarioLocal::where('local_id', $localId)
        ->get(['producto_almacen_id', 'cantidad']);

    // Devolvemos JSON: [{ producto_almacen_id: 1, cantidad: 12 }, ...]
    return response()->json($stocks);
}

    
}


