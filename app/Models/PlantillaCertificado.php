<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaCertificado extends Model
{
    public $timestamps = false;

    protected $table = 'plantilla_certificado';

    protected $primaryKey = 'plantilla_id';

    protected $fillable = ['categoria_id', 'contexto_id', 'nombre', 'imagen_path', 'layout', 'texto', 'tipo', 'tipo_reconocimiento_id', 'alcance', 'por_defecto'];

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

    public function tipoReconocimiento()
    {
        return $this->belongsTo(TipoReconocimiento::class, 'tipo_reconocimiento_id', 'tipo_reconocimiento_id');
    }

    /**
     * Una plantilla es dinámica cuando pertenece a un contexto y tiene layout definido.
     * En ese caso la imagen es sólo base (cabecera/borde) y el texto se compone.
     */
    public function esDinamica(): bool
    {
        return $this->contexto_id !== null && ! empty($this->layout);
    }
}
