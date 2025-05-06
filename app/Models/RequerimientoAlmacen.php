<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequerimientoAlmacen extends Model
{
    use HasFactory;

    protected $table = 'requerimiento_almacen';

    protected $fillable = [
        'codigo',
        'estado',
        'monto_total',
        'creado_por',
        'actualizado_por',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleRequerimientoAlmacen::class, 'requerimiento_almacen_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por', 'id');
    }
    
}
