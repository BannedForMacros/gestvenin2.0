<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleRequerimientoLocal extends Model
{
    protected $table = 'detalle_requerimientos_local';

    protected $fillable = ['requerimiento_local_id', 'producto_almacen_id', 'cantidad_requerida'];

    public function requerimientoLocal()
    {
        return $this->belongsTo(RequerimientoLocal::class, 'requerimiento_local_id');
    }

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}
