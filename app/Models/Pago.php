<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = ['venta_id', 'metodo_pago', 'monto'];

    /**
     * Relación con el modelo Venta.
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
