<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoPresentado extends Model
{
    public $timestamps = false;

    protected $table = 'documentos_presentados';

    protected $primaryKey = 'documento_id';

    protected $fillable = [
        'inscripcion_participante_id',
        'requisito_id',
        'path',
    ];

    public function requisito()
    {
        return $this->belongsTo(RequisitoDocumentacion::class, 'requisito_id', 'requisito_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(InscripcionParticipante::class, 'inscripcion_participante_id');
    }
}
