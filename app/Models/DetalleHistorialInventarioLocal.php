<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleHistorialInventarioLocal extends Model
{
    use HasFactory;

    protected $table = 'detalle_historial_inventario_local';

    protected $fillable = [
        'historial_inventario_local_id',
        'producto_almacen_id',
        'stock_inicial',
        'stock_final',
    ];

    public function historial()
    {
        return $this->belongsTo(HistorialInventarioLocal::class, 'historial_inventario_local_id');
    }

    public function productoAlmacen()
    {
        return $this->belongsTo(ProductoAlmacen::class, 'producto_almacen_id');
    }
    public function historialInventarioLocal()
{
    return $this->belongsTo(HistorialInventarioLocal::class, 'historial_inventario_local_id');
}

    
}
