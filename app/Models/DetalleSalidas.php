<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleSalidas extends Model
{
    use HasFactory;

    protected $table = 'detalles_salidas';

    protected $fillable = [
        'salida_almacen_id', 'producto_almacen_id', 'cantidad', 'precio_unitario', 'precio_total'
    ];

    // Relación con Salidas de Almacén
    public function salida()
    {
        return $this->belongsTo(SalidasAlmacen::class, 'salida_almacen_id');
    }

    // Relación con Productos del Almacén
    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
    
}
