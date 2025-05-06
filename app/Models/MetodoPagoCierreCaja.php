<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPagoCierreCaja extends Model
{
    use HasFactory;

    protected $table = 'metodo_pago_cierre_caja';

    protected $fillable = [
        'cierre_caja_id',
        'metodo',
        'monto',
    ];



    public function cierreCaja()
    {
        return $this->belongsTo(CierreCaja::class, 'cierre_caja_id');
    }
}
