<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id', 
        'nombre_cliente', 
        'direccion_cliente', 
        'numero_cliente', 
        'metodo_pago', 
        'user_id', 
        'hora_pedido', 
        'hora_entrega', 
        'estado', 
        'tiempo_demora'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function detalleDelivery()
    {
        return $this->hasOne(DetalleDelivery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}
