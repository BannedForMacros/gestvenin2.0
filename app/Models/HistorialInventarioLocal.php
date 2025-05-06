<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialInventarioLocal extends Model
{
    use HasFactory;

    protected $table = 'historial_inventario_local';

    protected $fillable = [
        'local_id',
        'total_productos',
        'fecha',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleHistorialInventarioLocal::class, 'historial_inventario_local_id');
    }

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }
}

