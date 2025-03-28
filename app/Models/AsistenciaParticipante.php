<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsistenciaParticipante extends Model
{
    use HasFactory;

    protected $table = 'asistencia_participante'; // Nombre de la tabla
    protected $primaryKey = 'asistencia_participante_id'; // Clave primaria
    public $timestamps = true; // Habilita timestamps

    protected $fillable = [
        'participante_id',
        'sesion_evento_id',
        'asistio'
    ];

    // Relación con el participante
    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    // Relación con la sesión del evento
    public function sesionEvento()
    {
        return $this->belongsTo(SesionEvento::class, 'sesion_evento_id', 'sesion_evento_id');
    }
}
