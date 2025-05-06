<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoAlmacen extends Model
{
    use HasFactory;

    protected $table = 'productos_almacen';

    protected $fillable = ['codigo', 'nombre', 'categoria_id', 'unidad_medida_id', 'activo'];

    // Relación con categorias
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Relación con unidades de medida
    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }
    // Relación con inventario local
    public function inventarioLocal()
    {
        return $this->hasMany(InventarioLocal::class, 'producto_almacen_id');
    }

    // Relación con InsumosPorProducto (productos de venta que consumen este producto de almacén)
    public function insumos()
    {
        return $this->hasMany(InsumosPorProducto::class, 'producto_almacen_id');
    }
    public function proveedor()
{
    return $this->belongsTo(Proveedor::class, 'proveedor_id');
}

}
