<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Models\ProductoAlmacen;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EntradaPdfController extends Controller
{
    public function generarResumenPdf(Request $request)
    {
        // Obtener datos del formulario
        $productos = $request->input('productos', []);
        $montoDado = $request->input('monto_dado_por_dueño', 0);
        $totalGasto = 0;
        $usuario = Auth::user();
        $nombreUsuario = $usuario->name;
    
        // Crear PDF con formato similar a generarPDF
        $pdf = new Fpdf('P', 'mm', 'A4');
        $pdf->AddPage();
    
        // Logo
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, -2, 25);
            $pdf->SetY(30); // Ajustar posición después del logo
        }
    
        // Encabezado
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, utf8_decode('Resumen de Entrada de Almacén'), 0, 1, 'C');
        $pdf->Ln(2);
    
        // Línea separadora
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(10);
    
        // Datos Generales
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 8, 'Fecha de Entrada:', 0, 0);
        $pdf->Cell(0, 8, $request->input('fecha_entrada'), 0, 1);
        $pdf->Cell(50, 8, 'Registrado por:', 0, 0);
        $pdf->Cell(0, 8, utf8_decode($nombreUsuario), 0, 1);
        $pdf->Cell(50, 8, 'Dinero Entregado:', 0, 0);
        $pdf->Cell(0, 8, 'S/ ' . number_format($montoDado, 2), 0, 1);
        $pdf->Ln(5);
    
        // Línea separadora
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(10);
    
        // Tabla de productos
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 10, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Comprobante', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Precio Unit.', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Subtotal', 1, 1, 'C', true);
    
        $pdf->SetFont('Arial', '', 12);
        foreach ($productos as $producto) {
            $nombreProducto = ProductoAlmacen::find($producto['producto_id'])->nombre ?? 'Producto no encontrado';
            $comprobante = $producto['comprobante'] ?? '';
            $cantidad = $producto['cantidad'];
            $precioUnitario = $producto['precio_unitario'];
            $precioTotal = $producto['precio_total'];
            $totalGasto += $precioTotal;
    
            $pdf->Cell(60, 8, utf8_decode($nombreProducto), 1);
            $pdf->Cell(40, 8, utf8_decode($comprobante), 1);
            $pdf->Cell(25, 8, number_format($cantidad, 2), 1, 0, 'R');
            $pdf->Cell(30, 8, 'S/ ' . number_format($precioUnitario, 2), 1, 0, 'R');
            $pdf->Cell(30, 8, 'S/ ' . number_format($precioTotal, 2), 1, 1, 'R');
        }
    
        // Totales
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(60 + 40, 8, '', 1, 0);
        $pdf->Cell(25, 8, '', 1, 0);
        $pdf->Cell(30, 8, 'TOTAL:', 1, 0, 'R');
        $pdf->Cell(30, 8, 'S/ ' . number_format($totalGasto, 2), 1, 1, 'R');
        $pdf->Ln(3);
    
        // Vuelto
        $vuelto = $montoDado - $totalGasto;
        $pdf->Cell(50, 8, 'Vuelto Entregado:', 0, 0);
        $pdf->Cell(0, 8, 'S/ ' . number_format($vuelto, 2), 0, 1);
    
        // Mensaje final
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'Documento generado automáticamente', 0, 1, 'C');
    
        // Salida del PDF
        $pdfContent = $pdf->Output('S', 'resumen_entrada.pdf');
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf');
    }
}
