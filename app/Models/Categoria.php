<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    // Definir los campos que se pueden rellenar
    protected $fillable = ['nombre', 'activo'];

    // Relación con productos
    public function productosAlmacen()
    {
        return $this->hasMany(ProductoAlmacen::class);
    }
}
