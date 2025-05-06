<?php
namespace App\Http\Controllers;

use App\Models\RequerimientoAlmacen;
use App\Models\DetalleRequerimientoAlmacen;
use App\Models\InventarioAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Models\ProductoAlmacen;
use Illuminate\Support\Facades\Log; // Importa la clase Log




class RequerimientoAlmacenController extends Controller
{
    public function index()
    {
        $requerimientos = RequerimientoAlmacen::with('detalles.producto')
	->orderBy('created_at','desc')
	->get();

        return view('requerimiento_almacen.index', compact('requerimientos'));
    }

    public function create(Request $request)
    {
        try {
            // Obtener productos con stock bajo o sin stock
            $productos = InventarioAlmacen::with('productoAlmacen')
                ->selectRaw('producto_almacen_id, SUM(cantidad) as cantidad_total, MAX(cantidad_minima) as cantidad_minima, MAX(precio_unitario) as precio_unitario')
                ->groupBy('producto_almacen_id')
                ->havingRaw('cantidad_total <= cantidad_minima OR cantidad_total = 0')
                ->get();
    
            // Estructurar productos
            $productosEstructurados = $productos->map(function ($producto) {
                return [
                    'id' => $producto->producto_almacen_id,
                    'nombre' => $producto->productoAlmacen->nombre,
                    'codigo' => $producto->productoAlmacen->codigo,
                    'cantidad_minima' => $producto->cantidad_minima,
                    'cantidad_sugerida' => ceil($producto->cantidad_minima * 1.5),
                    'precio_unitario' => $producto->precio_unitario,
                    'subtotal' => ceil($producto->cantidad_minima * 1.5) * $producto->precio_unitario,
                ];
            });
    
            // Verifica si es una solicitud AJAX
            if ($request->ajax()) {
                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los productos cumplen con la cantidad mínima.',
                    ], 200);
                }
    
                return response()->json([
                    'success' => true,
                    'productos' => $productosEstructurados,
                ], 200);
            }
    
            // Para solicitudes normales, devuelve la vista
            return view('requerimiento_almacen.create', compact('productosEstructurados'));
        } catch (\Exception $e) {
            Log::error('Error en el método create: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un problema al verificar los productos.',
            ], 500);
        }
    }
    
    
    
    
    

    public function store(Request $request)
    {
        try {
            // Generar el código único para el requerimiento
            $codigoRequerimiento = 'REQ-' . strtoupper(bin2hex(random_bytes(5)));
    
            // Crear el requerimiento
            $requerimiento = RequerimientoAlmacen::create([
                'codigo' => $codigoRequerimiento,
                'estado' => 'sin_confirmar',
                'creado_por' => Auth::id(),
                'monto_total' => 0, // Inicialmente 0
            ]);
    
            $montoTotal = 0;
    
            // Crear detalles del requerimiento
            foreach ($request->productos as $producto) {
                $subtotal = $producto['cantidad_sugerida'] * $producto['precio_unitario'];
                $montoTotal += $subtotal;
    
                DetalleRequerimientoAlmacen::create([
                    'requerimiento_almacen_id' => $requerimiento->id,
                    'producto_almacen_id' => $producto['producto_almacen_id'],
                    'cantidad_sugerida' => $producto['cantidad_sugerida'],
                    'precio_unitario' => $producto['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);
            }
    
            // Actualizar el monto total en el requerimiento
            $requerimiento->update(['monto_total' => $montoTotal]);
    
            // Respuesta JSON para AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Requerimiento creado correctamente.',
                    'redirect_url' => route('requerimiento_almacen.index'),
                ]);
            }
    
            return redirect()->route('requerimiento_almacen.index')->with('success', 'Requerimiento creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al generar requerimiento: ' . $e->getMessage());
    
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un problema al generar el requerimiento.',
                ], 500);
            }
    
            return redirect()->back()->withErrors('Ocurrió un problema al generar el requerimiento.');
        }
    }
    
    


        public function showPDF($id)
        {
            // Obtener los datos del requerimiento
            $requerimiento = RequerimientoAlmacen::with('detalles.producto')->findOrFail(id: $id);
    
            // Crear una instancia de FPDF
            $pdf = new Fpdf();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
    
            // Título del documento
            $pdf->Cell(190, 10, 'Requerimiento de Almacen', 0, 1, 'C');
            $pdf->Ln(10);
    
            // Información general del requerimiento
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(50, 10, 'Codigo: ' . $requerimiento->codigo, 0, 1);
            $pdf->Cell(50, 10, 'Estado: ' . $requerimiento->estado, 0, 1);
            $pdf->Cell(50, 10, 'Monto Total: S/ ' . number_format($requerimiento->monto_total, 2), 0, 1);
            $pdf->Ln(10);
    
            // Detalles del requerimiento
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(80, 10, 'Producto', 1, 0);
            $pdf->Cell(30, 10, 'Cantidad', 1, 0);
            $pdf->Cell(40, 10, 'Precio Unitario', 1, 0);
            $pdf->Cell(40, 10, 'Subtotal', 1, 1);
    
            $pdf->SetFont('Arial', '', 12);
            foreach ($requerimiento->detalles as $detalle) {
                $pdf->Cell(80, 10, $detalle->producto->nombre, 1, 0);
                $pdf->Cell(30, 10, $detalle->cantidad_sugerida, 1, 0, 'C');
                $pdf->Cell(40, 10, 'S/ ' . number_format($detalle->precio_unitario, 2), 1, 0, 'C');
                $pdf->Cell(40, 10, 'S/ ' . number_format($detalle->subtotal, 2), 1, 1, 'C');
            }
    
            // Enviar el PDF al navegador
            $pdf->Output();
            exit;
        }

        public function confirm($id)
        {

            $requerimiento = RequerimientoAlmacen::findOrFail($id);
        
            if (Auth::user()->hasRole('logistica') && $requerimiento->estado === 'sin_confirmar') {
                $requerimiento->update([
                    'estado' => 'no_atendido',
                    'actualizado_por' => Auth::id(),
                ]);
        
                return response()->json(['success' => true, 'message' => 'Requerimiento confirmado y enviado al administrador.']);
            }
        
            if (Auth::user()->hasRole('recaudo') && $requerimiento->estado === 'no_atendido') {
                $requerimiento->update([
                    'estado' => 'atendido',
                    'actualizado_por' => Auth::id(),
                ]);
        
                return response()->json(['success' => true, 'message' => 'Requerimiento atendido correctamente.']);
            }
        
            return response()->json(['success' => false, 'message' => 'Acción no permitida.'], 403);
        }
        
        
        
        

public function edit($id)
{
    $requerimiento = RequerimientoAlmacen::with('detalles.producto')->findOrFail($id);

    if (!Auth::user()->hasRole('logistica') && !Auth::user()->hasRole('admin')) {
        abort(403, 'Acción no permitida.');
    }

    return view('requerimiento_almacen.edit', compact('requerimiento'));
}


public function update(Request $request, $id)
{
    try {
        $requerimiento = RequerimientoAlmacen::findOrFail($id);

        if (!is_array($request->detalles)) {
            return response()->json([
                'success' => false,
                'message' => 'Los detalles enviados no son válidos.',
            ], 400);
        }

        foreach ($request->detalles as $detalle) {
            DetalleRequerimientoAlmacen::updateOrCreate(
                [
                    'requerimiento_almacen_id' => $requerimiento->id,
                    'producto_almacen_id' => $detalle['producto_id'],
                ],
                [
                    'cantidad_sugerida' => $detalle['cantidad_sugerida'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['cantidad_sugerida'] * $detalle['precio_unitario'],
                ]
            );
        }

        $montoTotal = $requerimiento->detalles->sum('subtotal');
        $requerimiento->update(['monto_total' => $montoTotal]);

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        Log::error('Error al guardar cambios en el requerimiento: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Error al guardar cambios.'], 500);
    }
}




public function show($id)
{
    $requerimiento = RequerimientoAlmacen::with(['detalles.producto', 'actualizadoPor'])->findOrFail($id);

    // Obtener productos disponibles en el almacén
    $productosAlmacen = ProductoAlmacen::where('activo', true)->get();

    // Inicializar permisos
    $puedeEditar = false;
    $puedeConfirmar = false;

    // Lógica para determinar permisos según el rol y el estado
    if (Auth::user()->hasRole('logistica') && $requerimiento->estado === 'sin_confirmar') {
        $puedeEditar = true;
        $puedeConfirmar = true;
    } elseif (Auth::user()->hasRole('recaudo') && $requerimiento->estado === 'no_atendido') {
        $puedeEditar = true;
        $puedeConfirmar = true;
    }

    // Pasar los datos necesarios a la vista
    return view('requerimiento_almacen.show', compact('requerimiento', 'productosAlmacen', 'puedeEditar', 'puedeConfirmar'));
}






}

