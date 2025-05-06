<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidasAlmacen extends Model
{
    use HasFactory;

    protected $table = 'salidas_almacen';

    protected $fillable = [
        'usuario_id', 'local_id', 'motivo', 'fecha_salida', 'observaciones',  'estado'
    ];

    // Relación con detalles de salidas
    public function detalles()
    {
        return $this->hasMany(DetalleSalidas::class, 'salida_almacen_id');
    }

    // Relación con usuarios
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    
    // Relación con locales
    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    // Relación con el requerimiento que satisface esta salida
    public function requerimientoLocal()
    {
        return $this->belongsTo(RequerimientoLocal::class, 'requerimiento_local_id');
    }
    public function entradaLocal()
    {
        return $this->hasOne(EntradasLocal::class, 'salida_almacen_id');
    }
}
