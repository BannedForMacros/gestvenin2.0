<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaAlmacen extends Model
{
    use HasFactory;

    protected $table = 'entradas_almacen';

    protected $fillable = [
        'user_id', 'fecha_entrada', 'total_gasto', 'monto_dado_por_dueño', 'vuelto_entregado', 'activo'
    ];

    // Relación con usuario que registró la entrada
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con detalle_entrada
    public function detallesEntrada()
    {
        return $this->hasMany(DetalleEntrada::class);
    }
    // Modelo EntradaAlmacen
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    

}
