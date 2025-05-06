<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre', 'direccion', 'telefono', 'activo'
    ];

    // Relación con entradas_almacen
    public function entradasAlmacen()
    {
        return $this->hasMany(EntradaAlmacen::class);
    }
}
