<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionMinimosLocal extends Model
{
    protected $table = 'configuracion_minimos_local';

    protected $fillable = ['local_id', 'producto_almacen_id', 'cantidad_minima'];

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}
