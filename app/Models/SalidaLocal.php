<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaLocal extends Model
{
    use HasFactory;

    protected $table = 'salidas_inventario_local';

    protected $fillable = [
        'producto_almacen_id',
        'local_id',
        'cantidad',
        'tipo_salida',
        'observacion',
    ];

    // Relación con producto almacenado
    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class);
    }

    // Relación con el local
    public function local()
    {
        return $this->belongsTo(Local::class);
    }
}
