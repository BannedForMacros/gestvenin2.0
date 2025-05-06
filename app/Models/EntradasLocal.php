<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntradasLocal extends Model
{
    protected $table = 'entradas_local';

    protected $fillable = ['local_id', 'usuario_id', 'fecha', 'status'];

    public function detalles()
    {
        return $this->hasMany(DetalleEntradasLocal::class, 'entrada_local_id');
    }

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    public function salidaAlmacen()
    {
        return $this->belongsTo(SalidasAlmacen::class, 'salida_almacen_id');
    }
}
