<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* ─────────────────────────────────────────
 | Controllers (todos los que usas)
 ───────────────────────────────────────── */
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\TipoGastoController;
use App\Http\Controllers\ClasificacionGastoController;
use App\Http\Controllers\ProductoVentaController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\ProductoAlmacenController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ConfiguracionMinimosLocalController;
use App\Http\Controllers\InsumosPorProductoController;
use App\Http\Controllers\RequerimientoLocalController;
use App\Http\Controllers\RequerimientoAlmacenController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InventarioLocalController;
use App\Http\Controllers\EntradasLocalController;
use App\Http\Controllers\SalidaLocalController;
use App\Http\Controllers\InventarioAlmacenController;
use App\Http\Controllers\EntradaAlmacenController;
use App\Http\Controllers\EntradaPdfController;
use App\Http\Controllers\SalidasAlmacenController;
use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\GastoVentaController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\HistorialInventarioController;
use App\Http\Controllers\HistorialInventarioLocalController;
use App\Http\Controllers\DiscrepanciaInventarioLocalController;
use App\Http\Controllers\AuditoriaReportController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\InventarioReportController;
use App\Http\Controllers\VentasReportController;
use App\Http\Controllers\GastosReportController;

/* ─────────────────────────────────────────
 | Rutas públicas
 ───────────────────────────────────────── */
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'      => Route::has('login'),
        'canRegister'   => Route::has('register'),
        'laravelVersion'=> Application::VERSION,
        'phpVersion'    => PHP_VERSION,
    ]);
});

/* Blade “públicas” sin Inertia */
Route::view('/filtrado',          'filtrado')->name('filtrado');
Route::view('/sin-permiso',       'sin_permiso')->name('sin_permiso');
Route::view('/sin-apertura',      'sin_apertura')->name('sin_apertura');

/* Auth scaffolding  */
require __DIR__.'/auth.php';

/* ─────────────────────────────────────────
 | Dashboard (Inertia)
 ───────────────────────────────────────── */
Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
      ->middleware(['auth', 'verified'])
      ->name('dashboard');

/* ─────────────────────────────────────────
 | Grupo AUTH general
 ───────────────────────────────────────── */
Route::middleware('auth')->group(function () {

    /* Perfil */
    Route::get   ('/profile',  [ProfileController::class,'edit'   ])->name('profile.edit');
    Route::patch ('/profile',  [ProfileController::class,'update' ])->name('profile.update');
    Route::delete('/profile',  [ProfileController::class,'destroy'])->name('profile.destroy');

    /* Users (CRUD) */
    Route::resource('users', UserController::class);

    /* Requerimientos Local */
    Route::resource('requerimientos_local', RequerimientoLocalController::class);
    Route::post ('/requerimientos_local/generar',                     [RequerimientoLocalController::class,'generarRequerimientos'])->name('requerimientos_local.generar');
    Route::post ('/requerimientos_local/{id}/confirm',                [RequerimientoLocalController::class,'confirm'              ])->name('requerimientos_local.confirm');
    Route::post ('/requerimientos_local/{id}/agregar-producto',       [RequerimientoLocalController::class,'agregarProducto'      ])->name('requerimientos_local.agregarProducto');
    Route::post ('/requerimientos_local/{detalleId}/actualizar-cantidad-enviada',[RequerimientoLocalController::class,'actualizarCantidadEnviada'])->name('requerimientos_local.actualizarCantidadEnviada');
    Route::post ('/requerimientos_local/{id}/atender',                [RequerimientoLocalController::class,'atenderRequerimiento' ])->name('requerimientos_local.atender');
    Route::post ('/requerimientos_local/{id}/actualizarObservaciones',[RequerimientoLocalController::class,'actualizarObservaciones'])->name('requerimientos_local.actualizarObservaciones');
    Route::get('requerimientos_local/{requerimiento}/edit', [RequerimientoLocalController::class, 'edit'])
    ->name('requerimientos_local.edit');
    Route::put('requerimientos_local/{requerimiento}', [RequerimientoLocalController::class, 'update'])
    ->name('requerimientos_local.update');
    /* Requerimiento Almacén */
    Route::prefix('requerimiento_almacen')->group(function () {
        Route::get   ('/',            [RequerimientoAlmacenController::class,'index' ])->name('requerimiento_almacen.index');
        Route::get   ('/create',      [RequerimientoAlmacenController::class,'create'])->name('requerimiento_almacen.create');
        Route::post  ('/store',       [RequerimientoAlmacenController::class,'store' ])->name('requerimiento_almacen.store');
        Route::get   ('/{id}/pdf',    [RequerimientoAlmacenController::class,'showPDF'])->name('requerimiento_almacen.pdf');
        Route::get   ('/{id}/show',   [RequerimientoAlmacenController::class,'show'  ])->name('requerimiento_almacen.show');
        Route::get   ('/{id}/edit',   [RequerimientoAlmacenController::class,'edit'  ])->name('requerimiento_almacen.edit');
        Route::patch ('/{id}',        [RequerimientoAlmacenController::class,'update'])->name('requerimiento_almacen.update');
        Route::patch ('/{id}/confirm',[RequerimientoAlmacenController::class,'confirm'])->name('requerimiento_almacen.confirm');
    });

    /* Deliveries */
    Route::resource('deliveries', DeliveryController::class)->except(['show']);

    /* API clasificaciones */
    Route::get('/api/clasificaciones/{tipo}', [GastoController::class,'getClasificaciones']);

    /* Inventario Local & Entradas Local */
    Route::resource('inventario_local', InventarioLocalController::class);
    Route::get ('inventario_local/registrar-stock-final/{local_id}', [InventarioLocalController::class,'registrarStockFinal'])->name('inventario_local.registrar_stock_final');
    Route::post('inventario_local/guardar-stock-final/{localId}',   [InventarioLocalController::class,'guardarStockFinal'   ])->name('inventario_local.guardar_stock_final');
    Route::get('/api/inventario-local/{localId}', [InventarioLocalController::class, 'apiStockActual']);


    Route::get ('entradas_local',                 [EntradasLocalController::class,'index'           ])->name('entradas_local.index');
    Route::get('/entradas_local/por_fecha', [EntradasLocalController::class, 'porFecha'])->name('entradas_local.por_fecha');
    Route::get ('entradas_local/{id}',            [EntradasLocalController::class,'show'            ])->name('entradas_local.show');
    Route::post('entradas_local/{id}/confirmar',  [EntradasLocalController::class,'confirmarEntrada'])->name('entradas_local.confirmar');

    /* Historial Inventario Local */
    Route::prefix('historial_inventario_local')->group(function () {
        Route::get('/',      [HistorialInventarioLocalController::class,'index'])->name('historial_inventario_local.index');
        Route::get('/{id}',  [HistorialInventarioLocalController::class,'show' ])->name('historial_inventario_local.show');
    });

    /* Salidas Local */
    Route::resource('salidas_local', SalidaLocalController::class);

    /* Gastos Ventas */
    Route::resource('gastos_ventas', GastoVentaController::class);
    Route::get('/gastos_ventas/{gastoVenta}/toggleStatus', [GastoVentaController::class,'toggleStatus'])->name('gastos_ventas.toggleStatus');

    /* Deliveries, Tickets, Ventas extras */
    Route::resource('ventas', VentasController::class)->except(['show']);
    Route::delete('/ventas/{venta}',                 [VentasController::class,'destroy'        ])->name('ventas.destroy');
    Route::get   ('/ventas/saldos',                  [VentasController::class,'getSaldos'      ]);
    Route::get   ('/ventas/pollos_vendidos',         [VentasController::class,'getPollosVendidos'])->name('ventas.pollos_vendidos');
    Route::post  ('/ventas/store',                   [VentasController::class,'store'          ])->name('ventas.store');
    Route::get   ('/ventas/info-productos',          [VentasController::class,'infoProductos'  ])->name('ventas.info.productos');
    Route::post  ('/ticket/generar',                 [TicketController::class,'generarTicket'  ])->name('ticket.generar');
    Route::get   ('/ventas/{id}/imprimir',           [TicketController::class,'imprimirVenta'  ])->name('ventas.imprimir');
    Route::post  ('/imprimir-ticket',                [TicketController::class,'imprimirTicket' ]);

    /* ───────────────
     |  Mantenimiento
     ─────────────── */

        Route::resource('tipos_gastos',          TipoGastoController::class);
        Route::resource('clasificaciones_gastos',ClasificacionGastoController::class);
        Route::resource('productos_ventas',      ProductoVentaController::class);
        Route::resource('categorias',            CategoriaController::class);
        Route::resource('unidades_medida',       UnidadMedidaController::class);
        Route::resource('productos_almacen',     ProductoAlmacenController::class);
        Route::resource('proveedores',           ProveedorController::class)->except(['show']);
        Route::resource('configuracion_minimos_local', ConfiguracionMinimosLocalController::class);

        Route::get ('/productos/buscar',                        [ProductoAlmacenController::class,'buscar'])->name('productos.buscar');
        Route::get ('/productos/{id}',                          [ProductoAlmacenController::class,'show' ])->name('productos.show');
        Route::delete('/productos_almacen/{productoAlmacen}',   [ProductoAlmacenController::class,'destroy'])->name('productos_almacen.destroy');

        /* Insumos por Producto Venta */
        Route::get ('/insumos',                                         [InsumosPorProductoController::class,'index' ])->name('insumos.index');
        Route::get ('/productos_ventas/{productoVenta}/insumos/create', [InsumosPorProductoController::class,'create'])->name('insumos.create');
        Route::post('/productos_ventas/{productoVenta}/insumos',        [InsumosPorProductoController::class,'store' ])->name('insumos.store');
        Route::get ('/productos_ventas/{productoVenta}/insumos/edit',   [InsumosPorProductoController::class,'edit'  ])->name('insumos.edit');
        Route::put ('/productos_ventas/{productoVenta}/insumos',        [InsumosPorProductoController::class,'update'])->name('insumos.update');

        /* Clasificaciones dinámicas */
        Route::get('/api/clasificaciones/{tipo_id}', function ($tipo_id) {
            return App\Models\ClasificacionGasto::where('tipo_gasto_id', $tipo_id)
                ->where('activo', 1)
                ->get();
    });

    /* ─────────
     |  Admin
     ───────── */

        /* Gastos & Cierres */
        Route::resource('gastos', GastoController::class);

        Route::prefix('cierres')->group(function () {
            Route::get ('/create', [CierreCajaController::class,'create'])->name('cierres.create');
            Route::post('/',       [CierreCajaController::class,'store' ])->name('cierres.store');
            Route::get ('/index',  [CierreCajaController::class,'index' ])->name('cierres.index');
            Route::get ('/{id}',   [CierreCajaController::class,'show'  ])->name('cierres.show');
            Route::get ('/{id}/audit',[CierreCajaController::class,'audit'])->name('cierres.audit');
        });

        /* Historial Inventario & Discrepancias */
        Route::get ('/historial-inventario',          [HistorialInventarioController::class,'index'])->name('historial_inventario.index');
        Route::get ('/historial-inventario/{id}',     [HistorialInventarioController::class,'show' ])->name('historial_inventario.show');

        Route::get ('discrepancias/{localId}/{fecha}',[HistorialInventarioLocalController::class,'mostrarDiscrepancias'])->name('discrepancias.show');
        Route::resource('discrepancia_inventario_local', DiscrepanciaInventarioLocalController::class);


    /* ─────────
     | Ventas
     ───────── */


    /* ─────────
     | Almacén
     ───────── */

        Route::resource('inventario_almacen', InventarioAlmacenController::class);
        Route::post('/inventario_almacen/actualizar_cantidad_minima',[InventarioAlmacenController::class,'actualizarCantidadMinima'])->name('inventario_almacen.actualizar_cantidad_minima');
        Route::get ('/cerrar-inventario',                            [InventarioAlmacenController::class,'cerrarInventarioDiario'])->name('inventario.cerrar');
        Route::post('/inventario/actualizar-lotes',                  [InventarioAlmacenController::class,'actualizarLotes'])->name('inventario_almacen.actualizar_lotes');
        Route::get ('/inventario/stock/{id}',                        [InventarioAlmacenController::class,'stockProducto']);

        /* Salidas Almacén */
        Route::resource('salidas_almacen', SalidasAlmacenController::class);
        Route::get ('/salidas_almacen/{id}/pdf',       [SalidasAlmacenController::class,'generarPDF'])->name('salidas_almacen.pdf');
        Route::put ('/salidas_almacen/{id}/cambiar-estado',[SalidasAlmacenController::class,'cambiarEstado'])->name('salidas_almacen.cambiarEstado');
        Route::get ('/salidas_almacen/atender_requerimiento/{id}',   [SalidasAlmacenController::class,'atenderRequerimiento'])->name('salidas_almacen.atenderRequerimiento');
        Route::get ('/salidas_almacen/requerimientos/{requerimiento_id}',[SalidasAlmacenController::class,'createFromRequerimiento'])->name('salidas_almacen.requerimientos');
        Route::post('salidas_requerimientos/store',  [SalidasAlmacenController::class,'storeRequerimiento'])->name('salidas_requerimientos.store');
        Route::get ('/salidas_almacen/create/{requerimiento_id?}',   [SalidasAlmacenController::class,'create'])->name('salidas_almacen.create');
        Route::get ('/salidas_almacen/lotes/{productoId}',           [SalidasAlmacenController::class,'obtenerLotes']);

        /* Entradas Almacén */
        Route::resource('entradas_almacen', EntradaAlmacenController::class);
        Route::post('/entradas_almacen/generar_pdf', [EntradaPdfController::class,'generarResumenPdf'])->name('entradas_almacen.generar_pdf');
        Route::get ('entradas_almacen/pdf/{id}',      [EntradaAlmacenController::class,'generarPDF'])->name('entradas_almacen.pdf');

        /* Productos sin stock / bajo stock */
        Route::get('/productos-stock-bajo', [InventarioAlmacenController::class,'productosConStockBajo'])->name('productos.stock.bajo');
        Route::get('/productos-sin-stock',  [InventarioAlmacenController::class,'productosSinStock'   ])->name('productos.sin.stock');
        Route::post('/actualizar-cantidad-minima', [InventarioAlmacenController::class,'actualizarCantidadMinima'])->name('inventario.actualizarCantidadMinima');

    /* ─────────
     | Reportes
     ───────── */

        /* Inventario */
        Route::get('/reportes',                      [ReporteController::class,'index'     ])->name('reportes.index');
        Route::get('/reportes/inventario',           [ReporteController::class,'inventario'])->name('reportes.inventario');
        Route::get('/reportes/inventario/mas_salida',[InventarioReportController::class,'masSalida']);
        Route::get('/reportes/inventario/mas_entrada',[InventarioReportController::class,'masEntrada']);
        Route::get('/reportes/inventario/mas_rotativo',[InventarioReportController::class,'masRotativo']);
        Route::get('/reportes/inventario/menor_stock',[InventarioReportController::class,'menorStock']);
        Route::get('/reportes/inventario/sin_movimiento',[InventarioReportController::class,'sinMovimiento']);
        Route::get('/reportes/inventario/detallado', [InventarioReportController::class,'vistaDetallado'])->name('reportes.detallado');
        Route::get('/reportes/inventario/detallado/datos',     [InventarioReportController::class,'reporteProducto'])->name('reportes.detallado.datos');
        Route::get('/reportes/inventario/detallado/categoria', [InventarioReportController::class,'reportePorCategoria'])->name('reportes.detallado.categoria');
        Route::get('/reportes/inventario/movimientos_ultimo_mes',[InventarioReportController::class,'obtenerMovimientosUltimoMes'])->name('reportes.movimientosUltimoMes');

        /* Ventas */
        Route::get('/reportes/ventas',                          [ReporteController::class,'ventas'])->name('reportes.ventas');
        Route::get('/reportes/ventas/pollos_vendidos',          [VentasReportController::class,'pollosVendidos'])->name('ventas.pollos_vendidos');
        Route::get('/reportes/ventas/mas_vendido',              [VentasReportController::class,'masVendido'])->name('ventas.mas_vendido');
        Route::get('/reportes/ventas/ingresos_egresos',         [VentasReportController::class,'ingresosEgresos'])->name('ventas.ingresos_egresos');
        Route::get('/reportes/ventas/metodo_pago',              [VentasReportController::class,'metodoPagoMasUtilizado'])->name('ventas.metodo_pago');
        Route::get('/reportes/ventas/ventas_por_local',         [VentasReportController::class,'ventasPorLocal'])->name('ventas.ventas_por_local');
        Route::get('/reportes/ventas/tendencia_ingresos',       [VentasReportController::class,'tendenciaIngresos'])->name('ventas.tendencia_ingresos');
        Route::get('/reportes/ventas/producto_mas_vendido',     [VentasReportController::class,'productoMasVendido'])->name('ventas.producto_mas_vendido');
        Route::get('/reportes/ventas/comparativa_dias',         [VentasReportController::class,'comparativaVentasDias'])->name('ventas.comparativa_dias');
        Route::get('/reportes/ventas/ventas_por_locales',       [VentasReportController::class,'ventasPorLocales']);
        Route::get('/reportes/gastos',                          [ReporteController::class,'gastos'])->name('reportes.gastos');

        /* Gastos */
        Route::get('/reportes/gastos/gastos_totales',           [GastosReportController::class,'gastosTotales'])->name('gastos.gastos_totales');
        Route::get('/reportes/gastos/comparativa_gastos',       [GastosReportController::class,'comparativaGastos'])->name('gastos.comparativa_gastos');
        Route::get('/reportes/gastos/gastos_por_tipo',          [GastosReportController::class,'gastosPorTipo'])->name('gastos.por_tipo');
        Route::get('/reportes/gastos/gastos_por_clasificacion', [GastosReportController::class,'gastosPorClasificacion'])->name('gastos.por_clasificacion');
        Route::get('/gastos-ventas',                            [GastosReportController::class,'gastosVentas'])->name('gastos.ventas');

        /* Auditoría */
        Route::get('reportes/auditoria',                        [AuditoriaReportController::class,'auditoria'])->name('reportes.auditoria');
        Route::get('reportes/auditoria/ingresos-egresos',       [AuditoriaReportController::class,'ingresosEgresos'])->name('auditoria.ingresos_egresos');
});

/* Fallback */
Route::fallback(fn () => abort(404, 'Página no encontrada'));
