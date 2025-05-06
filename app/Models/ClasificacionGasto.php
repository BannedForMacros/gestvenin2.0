<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificacionGasto extends Model
{
    use HasFactory;

    protected $table = 'clasificaciones_gastos';
    protected $fillable = ['tipo_gasto_id', 'nombre', 'descripcion', 'activo'];

    

    public function tipoGasto()
    {
        return $this->belongsTo(TipoGasto::class);
    }

    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }

    // Scope para filtrar solo clasificaciones activas
    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }
    
}
