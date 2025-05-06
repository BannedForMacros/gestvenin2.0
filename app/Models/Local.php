<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    use HasFactory;

    protected $table = 'locales'; // Especifica el nombre de la tabla si es diferente del plural del modelo

    protected $fillable = [
        'nombre_local',
    ];

    /**
     * Relación con los usuarios
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
