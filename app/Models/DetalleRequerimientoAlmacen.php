<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleRequerimientoAlmacen extends Model
{
    use HasFactory;

    protected $table = 'detalle_requerimiento_almacen';

    protected $fillable = [
        'requerimiento_almacen_id',
        'producto_almacen_id',
        'cantidad_sugerida',
        'precio_unitario',
        'subtotal',
    ];

    public function requerimiento()
    {
        return $this->belongsTo(RequerimientoAlmacen::class, 'requerimiento_almacen_id');
    }

    public function producto()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}
