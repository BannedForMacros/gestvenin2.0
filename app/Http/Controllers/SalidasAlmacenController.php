<?php

namespace App\Http\Controllers;

use App\Models\DetalleEntradasLocal;
use App\Models\DetalleRequerimientoLocal;
use App\Models\SalidasAlmacen;
use App\Models\DetalleSalidas;
use App\Models\EntradasLocal;
use App\Models\InventarioAlmacen;
use App\Models\ProductoAlmacen;
use App\Models\Local;
use App\Models\RequerimientoLocal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\DB; // Para DB::transaction, DB::beginTransaction
use Exception; 

class SalidasAlmacenController extends Controller
{
    /**
     * Muestra la lista de salidas registradas ordenadas por fecha descendente.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Se cargan las relaciones de usuario, local y detalles (con producto) para optimizar las consultas.
        $salidas = SalidasAlmacen::with(['usuario', 'local', 'detalles.productoAlmacen'])
            ->orderBy('fecha_salida', 'desc')
            ->get();

        return view('salidas_almacen.index', compact('salidas'));
    }

    /**
     * Muestra el formulario para crear una salida.
     * Si se recibe un requerimiento ID, se carga el requerimiento y se preselecciona el local.
     *
     * @param  int|null  $requerimiento_id
     * @return \Illuminate\View\View
     */
    public function create($requerimiento_id = null)
    {
        $requerimiento = null;
        if ($requerimiento_id) {
            $requerimiento = RequerimientoLocal::with('detalles.productoAlmacen')->findOrFail($requerimiento_id);
        }

        // Obtener todos los productos
        $productos = ProductoAlmacen::all();
        // Obtener locales distintos al "Almacén"
        $locales = Local::where('nombre_local', '!=', 'Almacen')->get();
        // Predefinir el motivo como 'local'
        $motivo = 'local';

        return view('salidas_almacen.create', [
            'requerimiento' => $requerimiento,
            'locales' => $locales,
            'motivo' => $motivo,
            'localSeleccionado' => $requerimiento ? $requerimiento->local_id : null,
            'productos' => $productos,
        ]);
    }

    /**
     * Almacena una nueva salida de almacén consumiendo del inventario unificado.
     * Si se asocia a un requerimiento, se actualiza su detalle.
     * Para salidas con motivo "local", se crea también una entrada en el local.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validación
        $request->validate([
            'motivo'                      => 'required',
            'local_id'                    => 'required_if:motivo,local',
            'fecha_salida'                => 'required|date',
            'detalles'                    => 'required|array',
            'detalles.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
            'detalles.*.cantidad'         => 'required|numeric|min:0.01',
        ]);
    
        // Iniciar la transacción manual
        DB::beginTransaction();
    
        try {
            // 1. Crear la cabecera de la salida
            $salida = new SalidasAlmacen();
            $salida->usuario_id   = Auth::id();
            $salida->local_id     = $request->motivo === 'local' ? $request->local_id : null;
            $salida->motivo       = $request->motivo;
            $salida->fecha_salida = $request->fecha_salida;
            $salida->observaciones= $request->observaciones;
            $salida->estado       = 1; 
            $salida->save();
    
            // 2. Iterar sobre cada detalle solicitado
            foreach ($request->detalles as $detalle) {
                $productoId         = $detalle['producto_almacen_id'];
                $cantidadRequerida  = $detalle['cantidad'];
    
                // Buscar en el inventario unificado
                $inventario = InventarioAlmacen::where('producto_almacen_id', $productoId)->first();
                if (!$inventario || $inventario->cantidad < $cantidadRequerida) {
                    throw new Exception("Stock insuficiente para el producto ID {$productoId}. Se requieren {$cantidadRequerida}, pero hay {$stockDisponible}.");
                }
    
                // Descontar stock
                $inventario->cantidad -= $cantidadRequerida;
                $inventario->save();
    
                // Crear detalle de la salida
                $detalleSalida = new DetalleSalidas();
                $detalleSalida->salida_almacen_id   = $salida->id;
                $detalleSalida->producto_almacen_id = $productoId;
                $detalleSalida->cantidad            = $cantidadRequerida;
                $detalleSalida->precio_unitario     = $inventario->precio_unitario;
                $detalleSalida->precio_total        = $inventario->precio_unitario * $cantidadRequerida;
                $detalleSalida->save();
    
                // Actualizar detalle del requerimiento si se envía un requerimiento_id
                if ($request->requerimiento_id) {
                    $detalleReq = DetalleRequerimientoLocal::where('requerimiento_local_id', $request->requerimiento_id)
                        ->where('producto_almacen_id', $productoId)
                        ->first();
                    if ($detalleReq) {
                        $detalleReq->cantidad_enviada += $cantidadRequerida;
                        $detalleReq->save();
                    }
                }
            }
    
            // 3. Actualizar requerimiento (opcional)
            if ($request->requerimiento_id) {
                $req = RequerimientoLocal::findOrFail($request->requerimiento_id);
                $req->estado = 'atendido';
                $req->save();
            }
    
            // 4. Si es salida "local", crear la entrada local
            if ($request->motivo === 'local') {
                $entradaLocal = new EntradasLocal();
                $entradaLocal->local_id         = $request->local_id;
                $entradaLocal->usuario_id       = Auth::id();
                $entradaLocal->fecha_entrada    = $salida->fecha_salida;
                $entradaLocal->estado           = 'pendiente';
                $entradaLocal->salida_almacen_id= $salida->id;
                $entradaLocal->save();
    
                // Crear los detalles de la entrada local a partir de la salida
                $detallesSalida = DetalleSalidas::where('salida_almacen_id', $salida->id)->get();
                foreach ($detallesSalida as $ds) {
                    DetalleEntradasLocal::create([
                        'entrada_local_id'    => $entradaLocal->id,
                        'producto_almacen_id' => $ds->producto_almacen_id,
                        'cantidad_entrada'    => $ds->cantidad,
                        'precio_unitario'     => $ds->precio_unitario,
                        'precio_total'        => $ds->precio_total,
                    ]);
                }
            }
    
            // Si llegamos aquí, todo fue exitoso
            DB::commit(); // Confirmar la transacción
    
            // Devolver JSON con éxito
            return response()->json([
                'success' => true,
                'message' => 'Salida registrada con éxito (consumiendo inventario unificado).',
                'redirect' => route('salidas_almacen.index')
            ]);
    
        } catch (\Exception $e) {
            // Si algo falla, hacemos rollback
            DB::rollBack();
            // Devolvemos JSON de error
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Muestra el formulario para editar una salida.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $salida = SalidasAlmacen::with('detalles.productoAlmacen')->findOrFail($id);
        $entrada = EntradasLocal::where('salida_almacen_id', $id)
            ->with('detalles.productoAlmacen')
            ->first();
        $productos = ProductoAlmacen::all();
        $locales = Local::all();
        $fechaSalida = $salida->fecha_salida;

        return view('salidas_almacen.edit', compact('salida', 'entrada', 'productos', 'locales', 'fechaSalida'));
    }

    /**
     * Actualiza una salida y la entrada asociada, permitiendo agregar nuevos productos.
     * Se resta el stock consumido previamente, se restauran esos montos y se vuelve a consumir
     * del inventario unificado según los nuevos detalles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID de la salida a actualizar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'motivo'        => 'required',
            'local_id'      => 'required_if:motivo,local',
            'fecha_salida'  => 'required|date',
            'detalles'      => 'required|array',
            'detalles.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
            'detalles.*.cantidad'           => 'required|numeric|min:0.01',
        ]);
    
        DB::beginTransaction();
    
        try {
            $salida  = SalidasAlmacen::with('detalles')->findOrFail($id);
            $entrada = EntradasLocal::where('salida_almacen_id', $id)->first();
    
            // 1. Restaurar el stock (sumarlo de nuevo) y eliminar detalles
            foreach ($salida->detalles as $detalleSalida) {
                $inv = InventarioAlmacen::where('producto_almacen_id', $detalleSalida->producto_almacen_id)->first();
                if ($inv) {
                    $inv->cantidad += $detalleSalida->cantidad;
                    $inv->save();
                }
                $detalleSalida->delete();
            }
    
            // Borrar detalles de la entrada asociada
            if ($entrada) {
                foreach ($entrada->detalles as $detEnt) {
                    $detEnt->delete();
                }
            }
    
            // 2. Actualizar la cabecera de la salida
            $salida->update([
                'motivo'       => $request->motivo,
                'local_id'     => $request->motivo === 'local' ? $request->local_id : null,
                'observaciones'=> $request->observaciones,
                'fecha_salida' => $request->fecha_salida,
            ]);
    
            // 3. Consumir inventario de nuevo según los nuevos detalles
            foreach ($request->detalles as $detalle) {
                $prodId           = $detalle['producto_almacen_id'];
                $cantidadRequerida= $detalle['cantidad'];
    
                $inv = InventarioAlmacen::where('producto_almacen_id', $prodId)->first();
                if (!$inv || $inv->cantidad < $cantidadRequerida) {
                    throw new \Exception("Stock insuficiente para el producto ID {$prodId}. Falta " . ($cantidadRequerida - ($inv->cantidad ?? 0)));
                }
    
                $inv->cantidad -= $cantidadRequerida;
                $inv->save();
    
                DetalleSalidas::create([
                    'salida_almacen_id'  => $salida->id,
                    'producto_almacen_id'=> $prodId,
                    'cantidad'           => $cantidadRequerida,
                    'precio_unitario'    => $inv->precio_unitario,
                    'precio_total'       => $inv->precio_unitario * $cantidadRequerida,
                ]);
            }
    
            // 4. Crear o actualizar la entrada asociada
            if (!$entrada && $request->motivo === 'local') {
                $entrada = new EntradasLocal();
                $entrada->local_id         = $request->local_id;
                $entrada->usuario_id       = Auth::id();
                $entrada->fecha_entrada    = $salida->fecha_salida;
                $entrada->estado           = 'pendiente';
                $entrada->salida_almacen_id= $salida->id;
                $entrada->save();
            } elseif ($entrada) {
                $entrada->update([
                    'local_id'      => $request->motivo === 'local' ? $request->local_id : null,
                    'fecha_entrada' => $salida->fecha_salida,
                ]);
            }
    
            // Crear los nuevos detalles para la entrada local
            if ($entrada && $request->motivo === 'local') {
                $detallesSalida = DetalleSalidas::where('salida_almacen_id', $salida->id)->get();
                foreach ($detallesSalida as $ds) {
                    DetalleEntradasLocal::create([
                        'entrada_local_id'    => $entrada->id,
                        'producto_almacen_id' => $ds->producto_almacen_id,
                        'cantidad_entrada'    => $ds->cantidad,
                        'precio_unitario'     => $ds->precio_unitario,
                        'precio_total'        => $ds->precio_total,
                    ]);
                }
            }
    
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Salida y entrada actualizadas correctamente.',
                'redirect'=> route('salidas_almacen.index')
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    

    /**
     * Muestra los detalles de una salida.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $salida = SalidasAlmacen::with('detalles.productoAlmacen')->findOrFail($id);
        return view('salidas_almacen.show', compact('salida'));
    }

    /**
     * Cambia el estado de una salida (activa/inactiva) y ajusta el inventario unificado.
     *
     * Para activar o desactivar se reintegra o se consume el stock según corresponda.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cambiarEstado($id)
    {
        $salida = SalidasAlmacen::with('detalles')->findOrFail($id);

        if ($salida->estado == 1) { // Si está activa, desactivarla y reintegrar el stock.
            $salida->estado = 0;
            foreach ($salida->detalles as $detalle) {
                $inventario = InventarioAlmacen::where('producto_almacen_id', $detalle->producto_almacen_id)->first();
                if ($inventario) {
                    $inventario->cantidad += $detalle->cantidad;
                    $inventario->save();
                }
            }
        } else { // Si está inactiva, activarla y consumir el stock nuevamente.
            $salida->estado = 1;
            foreach ($salida->detalles as $detalle) {
                $inventario = InventarioAlmacen::where('producto_almacen_id', $detalle->producto_almacen_id)->first();
                if ($inventario) {
                    if ($inventario->cantidad >= $detalle->cantidad) {
                        $inventario->cantidad -= $detalle->cantidad;
                        $inventario->save();
                    } else {
                        return redirect()->route('salidas_almacen.index')->withErrors([
                            'error' => 'No hay suficiente stock para activar esta salida en el producto ID ' . $detalle->producto_almacen_id
                        ]);
                    }
                } else {
                    return redirect()->route('salidas_almacen.index')->withErrors([
                        'error' => 'El inventario para el producto ID ' . $detalle->producto_almacen_id . ' no existe.'
                    ]);
                }
            }
        }

        $salida->save();
        return redirect()->route('salidas_almacen.index')
               ->with('success', 'Estado de la salida actualizado con éxito.');
    }

    /**
     * Crea una salida a partir de un requerimiento.
     *
     * @param int $requerimiento_id
     * @return \Illuminate\View\View
     */
    public function createFromRequerimiento($requerimiento_id)
    {
        $requerimiento = RequerimientoLocal::with('detalles.productoAlmacen')->findOrFail($requerimiento_id);
        $locales = Local::all();
        $productos = ProductoAlmacen::all();

        return view('salidas_almacen.create', [
            'requerimiento' => $requerimiento,
            'locales' => $locales,
            'productos' => $productos,
            'localSeleccionado' => $requerimiento->local_id,
            'motivo' => 'local',
        ]);
    }


    /**
     * Genera un PDF con los datos de la salida, mostrando motivo, local, fecha, usuario y los detalles de productos.
     *
     * Además se recomienda disponer de un botón en el index que redirija a esta acción para abrir el PDF en una nueva ventana.
     *
     * @param int $id
     * @return void
     */
    public function generarPDF($id)
    {
        $salida = SalidasAlmacen::with('detalles.productoAlmacen', 'local', 'usuario')
            ->findOrFail($id);
    
        $pdf = new Fpdf();
        $pdf->AddPage();
    
        // ===== LOGO (debes colocar el archivo en public/images/logo.png) =====
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, -2, 25); // x=10, y=6, tamaño menor (25mm)
            $pdf->SetY(12); // Empuja el cursor de escritura más abajo para que no se cruce con el logo
            }
    
        // ===== ENCABEZADO =====
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(0, 10, utf8_decode('Reporte de Salida de Almacén'), 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Line(10, 25, 200, 25);
        $pdf->Ln(6);
    
        // ===== DATOS GENERALES =====
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 8, utf8_decode('Motivo:'), 0, 0);
        $pdf->Cell(0, 8, utf8_decode(ucfirst($salida->motivo)), 0, 1);
    
        $pdf->Cell(40, 8, 'Local:', 0, 0);
        $pdf->Cell(0, 8, utf8_decode($salida->local->nombre_local ?? 'No especificado'), 0, 1);
    
        $pdf->Cell(40, 8, utf8_decode('Fecha de Salida:'), 0, 0);
        $pdf->Cell(0, 8, $salida->fecha_salida, 0, 1);
    
        $pdf->Cell(40, 8, 'Usuario:', 0, 0);
        $pdf->Cell(0, 8, utf8_decode($salida->usuario->name ?? 'No identificado'), 0, 1);
        $pdf->Ln(5);
    
        // ===== OBSERVACIONES (si existen) =====
        if ($salida->observaciones) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, 'Observaciones:', 0, 1);
            $pdf->SetFont('Arial', '', 12);
            $pdf->MultiCell(0, 8, utf8_decode($salida->observaciones), 0, 'L');
            $pdf->Ln(3);
        }
    
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
    
        // ===== DETALLES DE PRODUCTOS (sin precio) =====
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetTextColor(0, 0, 0);
    
        $pdf->Cell(130, 10, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(60, 10, 'Cantidad', 1, 1, 'C', true);
    
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->SetFillColor(255, 255, 255);
    
        foreach ($salida->detalles as $detalle) {
            $pdf->Cell(130, 8, utf8_decode($detalle->productoAlmacen->nombre ?? 'Producto no encontrado'), 1);
            $pdf->Cell(60, 8, number_format($detalle->cantidad, 2), 1, 1, 'R');
        }
    
        // ===== FOOTER =====
        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, utf8_decode('Documento generado automáticamente'), 0, 1, 'C');
    
        $pdf->Output("I", "Salida_Almacen_{$id}.pdf");
        exit;
    }
    
    
}
