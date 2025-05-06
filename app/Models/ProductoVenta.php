<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoVenta extends Model
{
    use HasFactory;

    protected $table = 'productos_ventas';

    // Campos permitidos para asignación masiva
    protected $fillable = ['nombre', 'descripcion', 'precio_delivery', 'equivalente_pollos', 'precio', 'estado'];

    // Solo productos activos
    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }
    
    // Relación con InsumosPorProducto (productos de almacén asociados a este producto de venta)
    public function insumos()
    {
        return $this->hasMany(InsumosPorProducto::class, 'producto_venta_id');
    }
    public function detallesVentas()
{
    return $this->hasMany(DetalleVenta::class, 'producto_venta_id');
}

}

