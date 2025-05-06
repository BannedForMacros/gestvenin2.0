<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleEntradasLocal extends Model
{
    protected $table = 'detalle_entradas_local';

    protected $fillable = ['entrada_local_id', 'producto_almacen_id', 'cantidad_entrada', 'precio_unitario', 'precio_total'];

    public function entradaLocal()
    {
        return $this->belongsTo(EntradasLocal::class, 'entrada_local_id');
    }

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}

