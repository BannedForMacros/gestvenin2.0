<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleDelivery extends Model
{
    use HasFactory;

    protected $table = 'detalle_delivery';

    protected $fillable = [
        'venta_id', 
        'costo_delivery'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
