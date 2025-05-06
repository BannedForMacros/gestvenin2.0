<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialInventario extends Model
{
    use HasFactory;

    protected $table = 'historial_inventario';

    protected $fillable = [
        'fecha',
        'total_productos',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleHistorialInventario::class, 'historial_inventario_id');
    }
}
