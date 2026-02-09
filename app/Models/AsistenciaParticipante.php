<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsistenciaParticipante extends Model
{
    use HasFactory;

    protected $table = 'asistencia_participante';
    protected $primaryKey = 'asistencia_participante_id';
    public $timestamps = false;

    protected $fillable = [
        'inscripcion_participante_id',
        'sesion_evento_id',
        'asistio'
    ];

    //acceder al participante a través de la inscripción: $asistencia->inscripcionParticipante->participante
    public function inscripcionParticipante()
    {
        return $this->belongsTo(InscripcionParticipante::class, 'inscripcion_participante_id');
    }

    // Relación con la sesión del evento
    public function sesionEvento()
    {
        return $this->belongsTo(SesionEvento::class, 'sesion_evento_id', 'sesion_evento_id');
    }
}
