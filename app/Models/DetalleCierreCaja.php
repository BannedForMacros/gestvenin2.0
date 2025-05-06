<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCierreCaja extends Model
{
    use HasFactory;

    protected $table = 'detalle_cierre_caja';

    protected $fillable = [
        'cierre_caja_id', 
        'producto', 
        'cantidad', 
        'precio_unitario', 
        'subtotal',
    ];

    public function cierreCaja()
    {
        return $this->belongsTo(CierreCaja::class, 'cierre_caja_id');
    }
}
