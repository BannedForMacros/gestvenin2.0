<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'local_id', 'total', 'fecha_venta', 'es_delivery', 'activo', 'metodo_pago'
    ];

    // Relación con el usuario que realizó la venta
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con el local donde se realizó la venta
    public function local()
    {
        return $this->belongsTo(Local::class);
    }

    // Relación con los detalles de la venta (productos vendidos en la venta)
    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    // Relación con el detalle del delivery, si es que la venta incluye un delivery
    public function detalleDelivery()
    {
        return $this->hasOne(DetalleDelivery::class, 'venta_id');
    }
     // Relación con el delivery, si la venta incluye un delivery
     public function delivery()
     {
         return $this->hasOne(Delivery::class, 'venta_id');
     }
     public function cierreCaja()
     {
         return $this->hasOneThrough(CierreCaja::class, DetalleCierreCaja::class, 'venta_id', 'id', 'id', 'cierre_caja_id');
     }
     public function pagos()
     {
         return $this->hasMany(Pago::class);
     }

     // app/Models/Venta.php
public function scopeFiltros($q, Request $r)
{
    $user = auth()->user();
    $fecha = $r->input('fecha_venta', now()->toDateString());

    $q->whereDate('fecha_venta', $fecha)
      ->where('activo', true);

    if ($user->hasRole('cajera')) {
        $q->where('local_id', $user->local_id);
    } else {
        if ($r->filled('local_id')) {
            $q->where('local_id', $r->local_id);
        }
    }
}

    
}
