<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\EntradaAlmacen;
use App\Models\DetalleEntrada;
use App\Models\DetalleSalidas;
use App\Models\InventarioAlmacen;
use App\Models\ProductoAlmacen;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Codedge\Fpdf\Fpdf\Fpdf;


class EntradaAlmacenController extends Controller
{
    /**
     * Muestra la lista de entradas registradas (ordenadas por fecha descendente).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $entradas = EntradaAlmacen::with('usuario')
            ->orderBy('fecha_entrada', 'desc')
            ->get();

        return view('entradas_almacen.index', compact('entradas'));
    }

    /**
     * Muestra el formulario para crear una nueva entrada.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categorias = Categoria::where('activo', true)->get();
        $productosAlmacen = ProductoAlmacen::with('categoria', 'unidadMedida')
            ->where('activo', true)
            ->get();
        $proveedores = Proveedor::where('activo', true)->get();

        return view('entradas_almacen.create', compact('categorias', 'productosAlmacen', 'proveedores'));
    }

    /**
     * Almacena una nueva entrada en la base de datos y actualiza el inventario unificado.
     *
     * Se agrupan los productos por ID para sumar las cantidades y totales; se crean registros
     * individuales en detalle_entrada y se actualiza o crea el registro correspondiente en inventario_almacen,
     * utilizando todas las entradas activas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha_entrada'               => 'required|date',
            'monto_dado_por_dueño'        => 'required|numeric',
            'productos'                   => 'required|array',
            'productos.*.producto_id'     => 'required|exists:productos_almacen,id',
            'productos.*.cantidad'        => 'required|numeric|min:0.01',
            'productos.*.precio_unitario' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            // Crear la entrada principal.
            $entrada = new EntradaAlmacen();
            $entrada->user_id = Auth::id();
            $entrada->fecha_entrada = $request->fecha_entrada;
            $entrada->monto_dado_por_dueño = $request->monto_dado_por_dueño;
            $entrada->total_gasto = 0; // Se actualizará más adelante.
            $entrada->activo = true;
            $entrada->save();

            $totalGasto = 0;

            // Agrupar productos por ID para consolidar cantidades y totales.
            $productosAgrupados = collect($request->productos)
                ->groupBy('producto_id')
                ->map(function ($items, $productoId) {
                    return [
                        'producto_id' => $productoId,
                        'cantidad'    => $items->sum('cantidad'),
                        'precio_total'=> $items->sum(function ($item) {
                            return $item['cantidad'] * $item['precio_unitario'];
                        }),
                        'detalles'    => $items, // Cada detalle se registra individualmente.
                    ];
                });

            foreach ($productosAgrupados as $productoData) {
                $productoId = $productoData['producto_id'];
                $producto = ProductoAlmacen::find($productoId);
                if (!$producto) {
                    throw new \Exception("Producto con ID {$productoId} no encontrado.");
                }

                // Registrar cada detalle individual.
                foreach ($productoData['detalles'] as $detalleData) {
                    $detalleEntrada = new DetalleEntrada();
                    $detalleEntrada->entrada_almacen_id = $entrada->id;
                    $detalleEntrada->producto_almacen_id = $productoId;
                    $detalleEntrada->proveedor_id = $detalleData['proveedor_id'] ?? null;
                    $detalleEntrada->cantidad_entrada = $detalleData['cantidad'];
                    $detalleEntrada->precio_unitario = $detalleData['precio_unitario'];
                    $detalleEntrada->precio_total = $detalleData['cantidad'] * $detalleData['precio_unitario'];
                    $detalleEntrada->comprobante = $detalleData['comprobante'] ?? null;
                    $detalleEntrada->save();
                }

                // Actualizar o crear el registro del inventario unificado para este producto.
                $inventario = InventarioAlmacen::where('producto_almacen_id', $productoId)->first();
                if ($inventario) {
                    $inventario->cantidad += $productoData['cantidad'];
                    $inventario->precio_total += $productoData['precio_total'];
                    $inventario->dinero_invertido += $productoData['precio_total'];
                    $inventario->precio_unitario = ($inventario->cantidad > 0)
                        ? round($inventario->precio_total / $inventario->cantidad, 2)
                        : 0;
                    $inventario->save();
                } else {
                    InventarioAlmacen::create([
                        'producto_almacen_id' => $productoId,
                        'cantidad'            => $productoData['cantidad'],
                        'precio_unitario'     => round($productoData['precio_total'] / $productoData['cantidad'], 2),
                        'precio_total'        => $productoData['precio_total'],
                        'dinero_invertido'    => $productoData['precio_total'],
                        'fecha'               => $request->fecha_entrada,
                    ]);
                }

                $totalGasto += $productoData['precio_total'];
            }

            // Actualizar la entrada principal con el total de gasto y calcular el vuelto.
            $entrada->total_gasto = $totalGasto;
            $entrada->vuelto_entregado = $entrada->monto_dado_por_dueño - $totalGasto;
            $entrada->save();

            DB::commit();
            return redirect()->route('inventario_almacen.index')
                ->with('success', 'Entrada registrada con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en store de EntradaAlmacenController: ' . $e->getMessage());
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * Muestra el detalle completo de una entrada.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $entrada = EntradaAlmacen::with('detallesEntrada.productoAlmacen.proveedor')
            ->findOrFail($id);

        return view('entradas_almacen.show', compact('entrada'));
    }

    /**
     * Muestra el formulario para editar una entrada existente.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $entrada = EntradaAlmacen::with([
            'detallesEntrada.productoAlmacen.categoria',
            'detallesEntrada.productoAlmacen.unidadMedida',
            'detallesEntrada.proveedor'
        ])->findOrFail($id);

        $categorias = Categoria::where('activo', true)->get();
        $productosAlmacen = ProductoAlmacen::with('categoria', 'unidadMedida')
            ->where('activo', true)
            ->get();
        $proveedores = Proveedor::where('activo', true)->get();

        return view('entradas_almacen.edit', compact('entrada', 'categorias', 'productosAlmacen', 'proveedores'));
    }

    /**
     * Actualiza una entrada existente y sus detalles, permitiendo agregar nuevos productos.
     *
     * Se valida que la suma global (de entradas activas, incluido lo que se edita) para cada producto
     * sea mayor o igual que las salidas registradas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID de la entrada a actualizar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha_entrada'               => 'required|date',
            'monto_dado_por_dueño'        => 'required|numeric',
            'productos'                   => 'required|array',
            'productos.*.producto_id'     => 'required|exists:productos_almacen,id',
            'productos.*.cantidad'        => 'required|numeric|min:0.01',
            'productos.*.precio_total'    => 'required|numeric',
            'productos.*.comprobante'     => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            // Recuperar la entrada a actualizar con sus detalles.
            $entrada = EntradaAlmacen::with('detallesEntrada')->findOrFail($id);

            // Agrupar en el request las cantidades nuevas por producto.
            $productosRequest = collect($request->productos)
                ->groupBy('producto_id')
                ->map(function ($items) {
                    return $items->sum('cantidad');
                });

            // Validar, para cada producto presente en esta entrada, que la suma de las entradas activas,
            // incluyendo lo que se envía ahora, sea mayor o igual que las salidas registradas.
            foreach ($entrada->detallesEntrada->groupBy('producto_almacen_id') as $productoId => $detalles) {
                $otrasEntradas = DetalleEntrada::where('producto_almacen_id', $productoId)
                    ->whereHas('entradaAlmacen', function ($q) use ($id) {
                        $q->where('activo', true)->where('id', '<>', $id);
                    })
                    ->sum('cantidad_entrada');

                $nuevoTotalEnEstaEntrada = $productosRequest->get($productoId, 0);
                $nuevoGlobal = $otrasEntradas + $nuevoTotalEnEstaEntrada;

                $totalSalidas = DetalleSalidas::where('producto_almacen_id', $productoId)
                    ->sum('cantidad');

                if ($nuevoGlobal < $totalSalidas) {
                    $producto = ProductoAlmacen::findOrFail($productoId);
                    throw new \Exception("Cantidad insuficiente para {$producto->nombre}. Mínimo requerido (global) es: {$totalSalidas} unidades.");
                }
            }

            // Procesar cada producto enviado en el request.
            $detalleIdsActualizados = collect();
            foreach ($request->productos as $productoData) {
                $productoId = $productoData['producto_id'];
                $dataUpdate = [
                    'proveedor_id'     => $productoData['proveedor_id'] ?? null,
                    'cantidad_entrada' => $productoData['cantidad'],
                    'precio_unitario'  => round($productoData['precio_total'] / $productoData['cantidad'], 2),
                    'precio_total'     => $productoData['precio_total'],
                    'comprobante'      => $productoData['comprobante'],
                ];

                // Si se envía 'detalle_id', se actualiza; de lo contrario, se crea uno nuevo.
                if (isset($productoData['detalle_id']) && $productoData['detalle_id']) {
                    $detalle = DetalleEntrada::find($productoData['detalle_id']);
                    if (!$detalle) {
                        throw new \Exception("Detalle con ID {$productoData['detalle_id']} no encontrado.");
                    }
                    $detalle->update($dataUpdate);
                } else {
                    $detalle = DetalleEntrada::create(array_merge([
                        'entrada_almacen_id'  => $entrada->id,
                        'producto_almacen_id' => $productoId,
                        'comprobante'         => $productoData['comprobante']
                    ], $dataUpdate));
                }
                $detalleIdsActualizados->push($detalle->id);
                // Recalcular el inventario global para este producto.
                $this->recalcularInventario($productoId, $request->fecha_entrada);
            }

            // Eliminar de la entrada los detalles que no se hayan enviado en el request.
            $entrada->detallesEntrada()
                ->whereNotIn('id', $detalleIdsActualizados->toArray())
                ->delete();

            // Actualizar los totales de la entrada (suma de precio_total de todos sus detalles).
            $totalGasto = collect($request->productos)->sum('precio_total');
            $entrada->update([
                'fecha_entrada'         => $request->fecha_entrada,
                'monto_dado_por_dueño'  => $request->monto_dado_por_dueño,
                'total_gasto'           => $totalGasto,
                'vuelto_entregado'      => $request->monto_dado_por_dueño - $totalGasto,
            ]);

            DB::commit();
            return response()->json(['success' => 'Entrada actualizada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando entrada: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Desactiva una entrada, removiéndola del cálculo del inventario global.
     *
     * Al desactivar una entrada se retira su contribución al inventario,
     * por lo que se recalcula el stock para cada producto involucrado.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deactivate($id)
    {
        DB::beginTransaction();

        try {
            $entrada = EntradaAlmacen::with('detallesEntrada')->findOrFail($id);
            $entrada->activo = false;
            $entrada->save();

            // Para cada producto involucrado en esta entrada, recalcular el inventario global.
            $productoIds = $entrada->detallesEntrada->pluck('producto_almacen_id')->unique();
            foreach ($productoIds as $productoId) {
                $this->recalcularInventario($productoId, $entrada->fecha_entrada);
            }

            DB::commit();
            return redirect()->route('inventario_almacen.index')
                ->with('success', 'Entrada desactivada y el inventario actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error desactivando entrada: ' . $e->getMessage());
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * Recalcula el inventario global para un producto, considerando solo las entradas activas.
     *
     * Suma todas las cantidades y totales de DetalleEntrada asociados al producto (de entradas activas)
     * y resta las salidas registradas para obtener el stock actual. Actualiza o crea el registro en InventarioAlmacen.
     *
     * @param int $productoId
     * @param string $fecha  Fecha a registrar en el inventario.
     * @return void
     */
    private function recalcularInventario($productoId, $fecha)
    {
        // Sumar todas las entradas activas para este producto.
        $cantidadTotal = DetalleEntrada::where('producto_almacen_id', $productoId)
            ->whereHas('entradaAlmacen', function ($q) {
                $q->where('activo', true);
            })
            ->sum('cantidad_entrada');

        $precioTotalSum = DetalleEntrada::where('producto_almacen_id', $productoId)
            ->whereHas('entradaAlmacen', function ($q) {
                $q->where('activo', true);
            })
            ->sum('precio_total');

        $precioUnitario = ($cantidadTotal > 0)
            ? round($precioTotalSum / $cantidadTotal, 2)
            : 0;

        // Sumar todas las salidas registradas para este producto (sin importar el estado de la entrada).
        $totalSalidas = DetalleSalidas::where('producto_almacen_id', $productoId)
            ->sum('cantidad');

        $stockActual = $cantidadTotal - $totalSalidas;

        // Actualizar o crear el registro unificado en el inventario.
        InventarioAlmacen::updateOrCreate(
            ['producto_almacen_id' => $productoId],
            [
                'cantidad'         => $stockActual,
                'precio_unitario'  => $precioUnitario,
                'precio_total'     => $precioTotalSum,
                'fecha'            => $fecha,
                'dinero_invertido' => $precioTotalSum,
            ]
        );
    }
    public function generarPDF($id)
    {
        // 1. Cargar la entrada con sus relaciones (usuario y detalles)
        $entrada = EntradaAlmacen::with([
            'usuario',
            'detallesEntrada.productoAlmacen'
        ])->findOrFail($id);
    
        // 2. Crear objeto FPDF y configurar la página
        $pdf = new \Codedge\Fpdf\Fpdf\Fpdf();
        $pdf->AddPage();
    
        // Logo: colocar el logo en la posición (x=10, y=-2) con ancho=25 mm
        $logoPath = public_path('images/logo.png'); // Asegúrate que el archivo esté en public/images/logo.png
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, -2, 25);
            // Ajusta la posición Y para que el texto no se superponga con el logo
            $pdf->SetY(12);
        }
    
        // Encabezado - Título
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(33, 37, 41); // Gris oscuro
        $pdf->Cell(0, 10, utf8_decode('Reporte de Entrada de Almacén'), 0, 1, 'C');
        $pdf->Ln(2);
    
        // Línea separadora
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Line(10, 25, 200, 25);
        $pdf->Ln(6);
    
        // 3. Datos Generales
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 8, utf8_decode('Fecha de Entrada:'), 0, 0);
        $pdf->Cell(0, 8, $entrada->fecha_entrada, 0, 1);
        $pdf->Cell(50, 8, utf8_decode('Usuario:'), 0, 0);
        $usuario = $entrada->usuario ? $entrada->usuario->name : 'No identificado';
        $pdf->Cell(0, 8, utf8_decode($usuario), 0, 1);
        $pdf->Cell(50, 8, 'Dinero Entregado:', 0, 0);
        $pdf->Cell(0, 8, 'S/ ' . number_format($entrada->monto_dado_por_dueño, 2), 0, 1);
        $pdf->Cell(50, 8, 'Total Gasto:', 0, 0);
        $pdf->Cell(0, 8, 'S/ ' . number_format($entrada->total_gasto, 2), 0, 1);
        $pdf->Cell(50, 8, 'Vuelto Entregado:', 0, 0);
        $pdf->Cell(0, 8, 'S/ ' . number_format($entrada->vuelto_entregado, 2), 0, 1);
        $pdf->Ln(5);
    
        // Línea separadora
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
    
        // 4. Detalles de la Entrada - Tabla
        // Definir encabezados con los siguientes anchos (en mm)
        // Producto: 60, Comprobante: 40, Cantidad: 25, Precio Unit.: 30, Subtotal: 30 (Total=185, que se ajusta en una A4 con márgenes)
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetTextColor(0, 0, 0);
    
        $pdf->Cell(60, 10, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Comprobante', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Precio Unit.', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Subtotal', 1, 1, 'C', true);
    
        // Resto de la tabla: iterar los detalles
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->SetFillColor(255, 255, 255);
    
        $sumaSubtotales = 0;
        foreach ($entrada->detallesEntrada as $detalle) {
            $nombreProducto = $detalle->productoAlmacen->nombre ?? 'Producto no encontrado';
            $comprobante    = $detalle->comprobante ?? '';
            $cantidad       = $detalle->cantidad_entrada;
            $precioUnit     = $detalle->precio_unitario;
            $precioTotal    = $detalle->precio_total;
    
            $pdf->Cell(60, 8, utf8_decode($nombreProducto), 1);
            $pdf->Cell(40, 8, utf8_decode($comprobante), 1);
            $pdf->Cell(25, 8, number_format($cantidad, 2), 1, 0, 'R');
            $pdf->Cell(30, 8, 'S/ '.number_format($precioUnit, 2), 1, 0, 'R');
            $pdf->Cell(30, 8, 'S/ '.number_format($precioTotal, 2), 1, 1, 'R');
    
            $sumaSubtotales += $precioTotal;
        }
    
        // Fila final con total
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(60 + 40, 8, '', 1, 0); // Celda combinada vacía para Producto y Comprobante
        $pdf->Cell(25, 8, '', 1, 0);
        $pdf->Cell(30, 8, utf8_decode('TOTAL:'), 1, 0, 'R');
        $pdf->Cell(30, 8, 'S/ '.number_format($sumaSubtotales, 2), 1, 1, 'R');
        $pdf->Ln(3);
    
        // 5. Mensaje final
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, utf8_decode('Documento generado automáticamente'), 0, 1, 'C');
    
        // 6. Salida del PDF
        $pdf->Output("I", "Entrada_Almacen_{$id}.pdf");
        exit;
    }
    
}
