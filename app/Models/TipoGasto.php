<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoGasto extends Model
{
    use HasFactory;

    protected $table = 'tipos_gastos';

    protected $fillable = ['nombre', 'descripcion'];

    public function clasificaciones()
    {
        return $this->hasMany(ClasificacionGasto::class);
    }
}
