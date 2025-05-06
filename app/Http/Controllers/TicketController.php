<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Models\ProductoVenta;
use App\Models\User;
use App\Models\Local;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class TicketController extends Controller
{
    public function generarTicket(Request $request)
    {
        // Obtener los datos enviados desde el formulario
        $productos = $request->input('productos', []);
        $total = $request->input('total', 0);
        $metodosPago = $request->input('pagos', []); // Arreglo de métodos de pago
        $user = Auth::user(); // Obtener el usuario autenticado
    
        // Obtener el local del usuario
        $local = $user->local; // Asumiendo que existe una relación local en el modelo User
        $nombreLocal = $local ? $local->nombre_local : 'Local no encontrado';
    
        // Obtener el nombre del usuario que generó la venta
        $nombreUsuario = $user->name;
    
        // Generar número de ticket y fecha/hora actual
        $ticketNumber = 'TK' . date('YmdHis');
        $dateTime = date('d/m/Y H:i:s');
    
        // Inicializar subtotal y tasa de impuesto
        $subtotal = 0;
    
        // Crear el ticket usando FPDF
        $pdf = new Fpdf();
        $pdf->AddPage('P', array(80, 200)); // Dimensiones de un ticket térmico
        $pdf->SetMargins(5, 5, 5);  // Ajuste de márgenes para ticket
        $pdf->SetAutoPageBreak(false);  // Evitar saltos automáticos de página
    
        // Encabezado de la empresa
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, 'Polleria Pollo Loco', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'RUC: 10412180289', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Direccion: Teatro 191', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Telefono: 9945943788', 0, 1, 'C');
    
        // Información adicional
        $pdf->Ln(2);
        $pdf->Cell(0, 5, 'Local: ' . $nombreLocal, 0, 1, 'C');
        $pdf->Cell(0, 5, 'Fecha: ' . $dateTime, 0, 1, 'C');
        $pdf->Cell(0, 5, 'Ticket Nro: ' . $ticketNumber, 0, 1, 'C');
    
        // Título del ticket
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, 'Ticket de Venta', 0, 1, 'C');
        $pdf->Ln(2);
    
        // Información del cajero
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, 'Cajero: ' . $nombreUsuario, 0, 1, 'L');
    
        $pdf->Ln(2);  // Separador
    
        // Encabezado de la tabla de productos
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'Producto', 0, 0, 'L');
        $pdf->Cell(10, 5, 'Cant.', 0, 0, 'C');
        $pdf->Cell(15, 5, 'P.U.', 0, 0, 'R');
        $pdf->Cell(15, 5, 'Total', 0, 1, 'R');
    
        // Detalles de los productos
        $pdf->SetFont('Arial', '', 8);
        foreach ($productos as $producto) {
            // Obtener el nombre del producto basado en el producto_id
            $productoModel = ProductoVenta::find($producto['producto_id']);
            $nombreProducto = $productoModel ? $productoModel->nombre : 'Producto no encontrado';
            $precioUnitario = $productoModel ? $productoModel->precio : 0;
            $cantidad = $producto['cantidad'];
            $itemTotal = $precioUnitario * $cantidad;
            $subtotal += $itemTotal;
    
            $pdf->Cell(30, 5, $nombreProducto, 0, 0, 'L');  // Producto
            $pdf->Cell(10, 5, $cantidad, 0, 0, 'C');  // Cantidad
            $pdf->Cell(15, 5, 'S/ ' . number_format($precioUnitario, 2), 0, 0, 'R');  // Precio unitario
            $pdf->Cell(15, 5, 'S/ ' . number_format($itemTotal, 2), 0, 1, 'R');  // Total por producto
        }
    
        // Totales
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 5, 'Subtotal: S/ ' . number_format($subtotal, 2), 0, 1, 'R');
        $pdf->Cell(0, 5, 'Total: S/ ' . number_format($total, 2), 0, 1, 'R');
    
        // Métodos de pago
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 5, 'Métodos de Pago:', 0, 1, 'L');
    
        $pdf->SetFont('Arial', '', 9);
        foreach ($metodosPago as $pago) {
            $metodo = ucfirst($pago['metodo']);
            $monto = number_format($pago['monto'], 2);
            $pdf->Cell(0, 5, "$metodo: S/ $monto", 0, 1, 'L');
        }
    
        // Mensaje final
        $pdf->Ln(5);
        $pdf->Cell(0, 5, 'Gracias por su compra', 0, 1, 'C');
    
        // Salida del PDF como respuesta
        $pdf->Output('I', 'ticket.pdf');
        exit;
    }

    public function imprimirTicket(Request $request)
    {
        // Validar los datos del ticket
        $productos = $request->input('productos', []);
        $total = $request->input('total', 0);
        $metodosPago = $request->input('pagos', []);
        $user = Auth::user(); // Usuario autenticado
    
        // Obtener el local del usuario
        $local = $user->local; // Relación local en el modelo User
        $nombreLocal = $local ? $local->nombre_local : 'Local no encontrado';
    
        // Obtener el nombre del usuario que generó la venta
        $nombreUsuario = $user->name;
    
        // Obtener el último ID de la tabla ventas
        $venta = Venta::latest()->first(); // Obtiene la venta más reciente
        $ventaId = $venta ? $venta->id : 0;
    
        // Verificar si el ID es válido
        if ($ventaId == 0) {
            return response()->json(['success' => false, 'message' => 'No se pudo obtener el ID de la venta.']);
        }
    
        // Generar el número de ticket usando el último ID de la venta
        $ticketNumber = 'TK' . date('YmdHi') . '-' . ($ventaId + 1);
        $dateTime = date('d/m/Y H:i:s');
    
        // Nombre de la impresora
        $nombreImpresora = "TP1";
    
        try {
            // Conectar con la impresora térmica
            $connector = new WindowsPrintConnector($nombreImpresora);
            $printer = new Printer($connector);
    
            // Imprimir el ticket dos veces
            for ($i = 0; $i < 1; $i++) {
                // Inicializar subtotal para cada impresión
                $subtotal = 0;
    
                // Encabezado de la empresa
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                $printer->text("Polleria Pollo Loco\n");
                $printer->selectPrintMode();
                $printer->text("RUC: 10412180289\n");
                $printer->text("Direccion: Teatro 191\n");
                $printer->text("Telefono: 9945943788\n");
    
                // Información adicional
                $printer->feed();
                $printer->text("Local: " . $nombreLocal . "\n");
                $printer->text("Fecha: " . $dateTime . "\n");
                $printer->text("Ticket Nro: " . $ticketNumber . "\n");
    
                // Título del ticket
                $printer->feed();
                $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                $printer->text("Ticket de Venta\n");
                $printer->selectPrintMode();
                $printer->feed();
    
                // Información del cajero
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text("Cajero: " . $nombreUsuario . "\n");
                $printer->text("------------------------------------------\n");
    
                // Encabezado de la tabla de productos
                $printer->setEmphasis(true);
                $printer->text(str_pad("Producto", 16));
                $printer->text(str_pad("Cant.", 6, ' ', STR_PAD_LEFT));
                $printer->text(str_pad("P.U.", 8, ' ', STR_PAD_LEFT));
                $printer->text(str_pad("Total", 8, ' ', STR_PAD_LEFT));
                $printer->text("\n");
                $printer->setEmphasis(false);
    
                // Detalles de los productos
                foreach ($productos as $producto) {
                    $productoModel = ProductoVenta::find($producto['producto_id']);
                    $nombreProducto = $productoModel ? $productoModel->nombre : 'Producto no encontrado';
                    $cantidad = $producto['cantidad'];
                    $precioUnitario = $productoModel ? $productoModel->precio : 0;
                    $itemTotal = $cantidad * $precioUnitario;
                    $subtotal += $itemTotal;
    
                    $printer->text(str_pad(substr($nombreProducto, 0, 16), 16));
                    $printer->text(str_pad($cantidad, 6, ' ', STR_PAD_LEFT));
                    $printer->text(str_pad('S/' . number_format($precioUnitario, 2), 8, ' ', STR_PAD_LEFT));
                    $printer->text(str_pad('S/' . number_format($itemTotal, 2), 8, ' ', STR_PAD_LEFT));
                    $printer->text("\n");
                }
    
                $printer->text("--------------------------------------------\n");
    
                // Totales
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("Subtotal: S/ " . number_format($subtotal, 2) . "\n");
                $printer->text("Total: S/ " . number_format($total, 2) . "\n");
    
                // Métodos de pago
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->feed();
                $printer->setEmphasis(true);
                $printer->text("Métodos de Pago:\n");
                $printer->setEmphasis(false);
                foreach ($metodosPago as $pago) {
                    $metodo = ucfirst($pago['metodo']);
                    $monto = number_format($pago['monto'], 2);
                    $printer->text("$metodo: S/ $monto\n");
                }
    
                // Mensaje final
                $printer->feed(2);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("Gracias por su compra\n");
                $printer->text("Polleria Pollo Loco\n");
    
                // Mensaje adicional
                $printer->selectPrintMode(Printer::MODE_FONT_B);
                $printer->text("Esto no es un comprobante de pago, si desea uno, acérquese a caja\n");
                $printer->selectPrintMode();
    
                $printer->feed(1);
    
                // Cortar el papel
                $printer->cut(Printer::CUT_PARTIAL);
            }
    
            // Cerrar la conexión con la impresora
            $printer->close();
    
            // Respuesta exitosa
            return response()->json(['success' => true, 'message' => 'Ticket impreso correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al imprimir el ticket: ' . $e->getMessage()]);
        }
    }
    
    public function imprimirVenta($id)
    {
        $venta = Venta::with(['detalles.producto', 'local', 'user', 'pagos'])->findOrFail($id);

        // Datos de la venta
        $user = $venta->user; 
        $nombreUsuario = $user ? $user->name : 'Usuario Desconocido';

        $local = $venta->local;
        $nombreLocal = $local ? $local->nombre_local : 'Local no encontrado';

        $productos = $venta->detalles->map(function($detalle) {
            return [
                'producto_id' => $detalle->producto_id,
                'cantidad' => $detalle->cantidad
            ];
        })->toArray();

        $metodosPago = $venta->pagos->map(function($pago) {
            return [
                'metodo' => $pago->metodo_pago,
                'monto' => $pago->monto
            ];
        })->toArray();

        $total = $venta->total;
        $dateTime = date('d/m/Y H:i:s');
        
        // Generar el número de ticket usando el ID de la venta
        // Puedes usar el ID directamente o agregar fecha/hora
        $ticketNumber = 'TK' . date('YmdHi') . '-' . $venta->id;

        // Nombre de la impresora (ajústalo a tu configuración)
        $nombreImpresora = "TP1";

        try {
            $connector = new WindowsPrintConnector($nombreImpresora);
            $printer = new Printer($connector);

            // Imprimir el ticket (una vez o más si lo deseas)
            for ($i = 0; $i < 1; $i++) {
                $subtotal = 0;

                // Encabezado
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                $printer->text("Polleria Pollo Loco\n");
                $printer->selectPrintMode();
                $printer->text("RUC: 10412180289\n");
                $printer->text("Direccion: Teatro 191\n");
                $printer->text("Telefono: 9945943788\n");

                $printer->feed();
                $printer->text("Local: " . $nombreLocal . "\n");
                $printer->text("Fecha: " . $dateTime . "\n");
                $printer->text("Ticket Nro: " . $ticketNumber . "\n");

                $printer->feed();
                $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                $printer->text("Ticket de Venta\n");
                $printer->selectPrintMode();
                $printer->feed();

                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text("Cajero: " . $nombreUsuario . "\n");
                $printer->text("------------------------------------------\n");

                // Tabla productos
                $printer->setEmphasis(true);
                $printer->text(str_pad("Producto", 16));
                $printer->text(str_pad("Cant.", 6, ' ', STR_PAD_LEFT));
                $printer->text(str_pad("P.U.", 8, ' ', STR_PAD_LEFT));
                $printer->text(str_pad("Total", 8, ' ', STR_PAD_LEFT));
                $printer->text("\n");
                $printer->setEmphasis(false);

                // Detalles de productos
                foreach ($productos as $prod) {
                    $productoModel = ProductoVenta::find($prod['producto_id']);
                    $nombreProducto = $productoModel ? $productoModel->nombre : 'No encontrado';
                    $cantidad = $prod['cantidad'];
                    $precioUnitario = $productoModel ? $productoModel->precio : 0;
                    $itemTotal = $precioUnitario * $cantidad;
                    $subtotal += $itemTotal;

                    $printer->text(str_pad(substr($nombreProducto, 0, 16), 16));
                    $printer->text(str_pad($cantidad, 6, ' ', STR_PAD_LEFT));
                    $printer->text(str_pad('S/' . number_format($precioUnitario, 2), 8, ' ', STR_PAD_LEFT));
                    $printer->text(str_pad('S/' . number_format($itemTotal, 2), 8, ' ', STR_PAD_LEFT));
                    $printer->text("\n");
                }

                $printer->text("--------------------------------------------\n");

                // Totales
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("Subtotal: S/ " . number_format($subtotal, 2) . "\n");
                $printer->text("Total: S/ " . number_format($total, 2) . "\n");

                // Métodos de pago
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->feed();
                $printer->setEmphasis(true);
                $printer->text("Métodos de Pago:\n");
                $printer->setEmphasis(false);
                foreach ($metodosPago as $pago) {
                    $metodo = ucfirst($pago['metodo']);
                    $monto = number_format($pago['monto'], 2);
                    $printer->text("$metodo: S/ $monto\n");
                }

                $printer->feed(2);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("Gracias por su compra\n");
                $printer->text("Polleria Pollo Loco\n");

                $printer->selectPrintMode(Printer::MODE_FONT_B);
                $printer->text("Esto no es un comprobante de pago, si desea uno, acérquese a caja\n");
                $printer->selectPrintMode();

                $printer->feed(1);
                $printer->cut(Printer::CUT_PARTIAL);
            }

            $printer->close();

            return redirect()->back()->with('success', 'Ticket impreso correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error al imprimir el ticket: ' . $e->getMessage()]);
        }
    }
    
    
}
