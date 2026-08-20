<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carrera';

    protected $fillable = ['nombre', 'codigo', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function titulosIntermedios()
    {
        return $this->hasMany(TituloIntermedio::class, 'carrera_id');
    }
}
