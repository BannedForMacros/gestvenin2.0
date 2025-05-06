<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleHistorialInventario extends Model
{
    use HasFactory;

    protected $table = 'detalle_historial_inventario';

    protected $fillable = [
        'historial_inventario_id',
        'producto_almacen_id',
        'lote',
        'cantidad',
        'precio_unitario',
        'dinero_invertido'
    ];

    public function historial()
    {
        return $this->belongsTo(HistorialInventario::class, 'historial_inventario_id');
    }

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
}
