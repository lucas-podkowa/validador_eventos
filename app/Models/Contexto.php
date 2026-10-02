<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Contexto extends Model
{
    public $timestamps = false;

    protected $table = 'contexto';

    protected $primaryKey = 'contexto_id';

    protected $fillable = [
        'categoria_id',
        'parent_id',
        'nombre',
        'tipo',
        'denominacion',
        'institucion',
        'anio',
        'fecha_inicio',
        'fecha_fin',
        'lugar',
        'resolucion',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'anio' => 'integer',
        'activo' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaEvento::class, 'categoria_id');
    }

    public function padre()
    {
        return $this->belongsTo(Contexto::class, 'parent_id', 'contexto_id');
    }

    public function hijos()
    {
        return $this->hasMany(Contexto::class, 'parent_id', 'contexto_id');
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'contexto_id', 'contexto_id');
    }

    public function emisiones()
    {
        return $this->morphMany(Emision::class, 'origen', 'origen_type', 'origen_id');
    }

    public function esEdicion(): bool
    {
        return $this->tipo === 'edicion';
    }

    public function firmantes()
    {
        return $this->belongsToMany(Firmante::class, 'contexto_firmante', 'contexto_id', 'firmante_id')
            ->withPivot('orden', 'mostrar_cargo')
            ->orderBy('contexto_firmante.orden');
    }

    public function plantillas()
    {
        return $this->hasMany(PlantillaCertificado::class, 'contexto_id', 'contexto_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
