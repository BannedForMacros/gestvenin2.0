<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioLocal extends Model
{
    protected $table = 'inventario_local';

    protected $fillable = [
        'producto_almacen_id',
        'local_id',
        'cantidad',
        'fecha',
        'precio_unitario',
        'precio_total'
    ];

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function configuracionMinimosLocal()
    {
        return $this->hasOne(ConfiguracionMinimosLocal::class, 'producto_almacen_id', 'producto_almacen_id')
                    ->where('local_id', $this->local_id);
    }
}

