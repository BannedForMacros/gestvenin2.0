<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


    class DetalleVenta extends Model

    {
        use HasFactory;
        protected $table = 'detalle_venta'; // Si el nombre es singular
    
    
    protected $fillable = [
        'venta_id', 'producto_id', 'cantidad', 'precio_unitario', 'total'
    ];

    // Relación con la venta a la que pertenece el detalle
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    // Relación con el producto vendido
    public function producto()
    {
        return $this->belongsTo(ProductoVenta::class);
    }
}
