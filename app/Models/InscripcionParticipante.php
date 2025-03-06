<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InscripcionParticipante extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'inscripcion_participante';
    protected $primaryKey = 'inscripcion_participante_id';
    protected $fillable = ['planilla_id', 'participante_id', 'fecha_inscripcion', 'asistencia'];


    public function indicadores()
    {
        return $this->belongsToMany(Indicador::class, 'participante_indicador', 'insc_participante_id', 'indicador_id');
    }

    public function planilla()
    {
        return $this->belongsTo(PlanillaInscripcion::class, 'planilla_id');
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id');
    }
}
