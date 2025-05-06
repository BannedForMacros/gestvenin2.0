<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleEntrada extends Model
{
    use HasFactory;

    protected $table = 'detalle_entrada';

    protected $fillable = [
        'entrada_almacen_id', 'producto_almacen_id', 'proveedor_id', 'cantidad_entrada', 'precio_unitario', 'precio_total', 'comprobante'
    ];

    // Relación con entrada_almacen
    public function entradaAlmacen()
    {
        return $this->belongsTo(EntradaAlmacen::class);
    }

    // Relación con productos_almacen
    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class);
    }

    // Relación con proveedores
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
    
}
