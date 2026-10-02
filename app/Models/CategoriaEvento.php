<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaEvento extends Model
{
    public $timestamps = false;

    protected $table = 'categoria_evento';

    protected $primaryKey = 'categoria_id';

    protected $fillable = ['nombre', 'descripcion', 'disponible_para_eventos'];

    protected $casts = [
        'disponible_para_eventos' => 'boolean',
    ];

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'categoria_id');
    }

    public function contextos()
    {
        return $this->hasMany(Contexto::class, 'categoria_id', 'categoria_id');
    }

    public function scopeDisponiblesParaEventos($query)
    {
        return $query->where('disponible_para_eventos', true);
    }
}
