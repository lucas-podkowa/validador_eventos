<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaEvento extends Model
{
    public $timestamps = false;

    protected $table = 'categoria_evento';

    protected $primaryKey = 'categoria_id';

    protected $fillable = ['nombre', 'descripcion'];

    public function plantillas()
    {
        return $this->hasMany(PlantillaCertificado::class, 'categoria_id');
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'categoria_id');
    }
}
