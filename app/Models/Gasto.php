<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    // Tabla asociada al modelo
    protected $table = 'gastos';

    // Campos asignables en masa
    protected $fillable = [
        'local_id',
        'nombre',
        'descripcion',
        'monto',
        'cantidad',
        'fecha_gasto',
        'tipo_gasto_id',
        'clasificacion_gasto_id',
        'origen',
        'origen_id', 
        'comprobante_de_pago',
        'activo',
    ];

    /**
     * Relación con la tabla `locales`.
     * Un gasto puede pertenecer a un local.
     */
    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    /**
     * Relación con la tabla `tipos_gastos`.
     * Un gasto tiene un tipo (por ejemplo: operativo o administrativo).
     */
    public function tipoGasto()
    {
        return $this->belongsTo(TipoGasto::class, 'tipo_gasto_id');
    }

    /**
     * Relación con la tabla `clasificaciones_gastos`.
     * Un gasto tiene una clasificación específica.
     */
    public function clasificacionGasto()
    {
        return $this->belongsTo(ClasificacionGasto::class, 'clasificacion_gasto_id');
    }
}
