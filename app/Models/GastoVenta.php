<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GastoVenta extends Model
{
    use HasFactory;
    protected $table = 'gastos_ventas';
    protected $fillable = [
        'nombre',
        'descripcion',
        'monto',
        'fecha_gasto',
        'local_id',
        'tipo_gasto_id',
        'clasificacion_gasto_id',
        'activo',
        'comprobante_de_pago',
    ];

    /**
     * Relación con el modelo TipoGasto.
     */
    public function tipoGasto()
    {
        return $this->belongsTo(TipoGasto::class, 'tipo_gasto_id');
    }

    /**
     * Relación con el modelo ClasificacionGasto.
     */
    public function clasificacionGasto()
    {
        return $this->belongsTo(ClasificacionGasto::class, 'clasificacion_gasto_id');
    }

    /**
     * Relación con el modelo Local.
     */
    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }
}
