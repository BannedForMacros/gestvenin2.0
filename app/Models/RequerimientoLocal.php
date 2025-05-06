<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequerimientoLocal extends Model
{
    protected $table = 'requerimientos_local';  // Asegúrate de que coincide con tu tabla

    protected $fillable = ['local_id', 'usuario_id', 'observaciones', 'fecha_requerimiento', 'estado']; // Incluye 'estado'

    // Relación con los detalles de requerimiento
    public function detalles()
    {
        return $this->hasMany(DetalleRequerimientoLocal::class, 'requerimiento_local_id');
    }

    // Relación con el local
    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    // Relación con el usuario que creó el requerimiento
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
