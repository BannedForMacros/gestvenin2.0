<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioAlmacen extends Model
{
    use HasFactory;

    protected $table = 'inventario_almacen';

    protected $fillable = [
        'producto_almacen_id', 'fecha', 'cantidad', 'cantidad_minima', 'precio_unitario', 'precio_total', 'dinero_invertido'
    ];

    // Relación con productos_almacen
    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class);
    }
}
