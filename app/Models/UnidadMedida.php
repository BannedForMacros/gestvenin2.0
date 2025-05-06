<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    use HasFactory;

    protected $table = 'unidades_medida';

    // Añadimos 'codigo' a los campos que pueden ser rellenados masivamente
    protected $fillable = ['nombre', 'codigo', 'activo'];

    // Relación con productos_almacen
    public function productosAlmacen()
    {
        return $this->hasMany(ProductoAlmacen::class);
    }
}
