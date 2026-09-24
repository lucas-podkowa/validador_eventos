<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaCertificado extends Model
{
    public $timestamps = false;

    protected $table = 'plantilla_certificado';

    protected $primaryKey = 'plantilla_id';

    public const TIPOS = ['asistencia', 'aprobacion', 'disertante', 'colaborador', 'tutor_academico'];

    protected $fillable = ['categoria_id', 'contexto_id', 'nombre', 'imagen_path', 'layout', 'texto', 'tipo', 'por_defecto'];

    protected $casts = [
        'layout' => 'array',
        'por_defecto' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaEvento::class, 'categoria_id');
    }

    public function contexto()
    {
        return $this->belongsTo(Contexto::class, 'contexto_id', 'contexto_id');
    }

    /**
     * Una plantilla es dinámica cuando pertenece a un contexto y tiene layout definido.
     * En ese caso la imagen es sólo base (cabecera/borde) y el texto se compone.
     */
    public function esDinamica(): bool
    {
        return $this->contexto_id !== null && ! empty($this->layout);
    }

    public function scopeDinamicas($query)
    {
        return $query->whereNotNull('contexto_id')->whereNotNull('layout');
    }

    public function scopeLegacy($query)
    {
        return $query->whereNull('contexto_id');
    }

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorDefecto($query)
    {
        return $query->where('por_defecto', true);
    }
}
