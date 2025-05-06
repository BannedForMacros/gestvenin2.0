<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    use HasFactory;

    protected $table = 'cierre_caja';

    protected $fillable = [
        'local_id', 
        'fecha_cierre', 
        'total_ventas', 
        'total_gastos', 
        'balance_final',
        'total_pollos_vendidos',
    ];


    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function gastos()
    {
        return $this->hasMany(GastoVenta::class, 'local_id', 'local_id')
                    ->whereDate('fecha_gasto', $this->fecha_cierre)
                    ->where('activo', true);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCierreCaja::class, 'cierre_caja_id');
    }

    public function metodosPago()
    {
        return $this->hasMany(MetodoPagoCierreCaja::class, 'cierre_caja_id');
    }
}
