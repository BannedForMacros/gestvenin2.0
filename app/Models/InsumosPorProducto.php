<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsumosPorProducto extends Model
{
    use HasFactory;

    protected $table = 'insumos_por_producto';

    // Campos permitidos para asignación masiva
    protected $fillable = ['producto_venta_id', 'producto_almacen_id', 'cantidad'];

    // Relación con ProductoVenta
    public function productoVenta()
    {
        return $this->belongsTo(ProductoVenta::class, 'producto_venta_id');
    }

    // Relación con ProductoAlmacen
    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}
