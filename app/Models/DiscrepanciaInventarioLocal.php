<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscrepanciaInventarioLocal extends Model
{
    use HasFactory;

    protected $table = 'discrepancia_inventario_local';

    protected $fillable = [
        'local_id',
        'producto_almacen_id',
        'fecha',
        'consumo_teorico',
        'consumo_real',
        'diferencia',
    ];

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}
