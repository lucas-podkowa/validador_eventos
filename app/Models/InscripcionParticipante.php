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
    protected $fillable = ['planilla_id', 'participante_id', 'rol_id', 'fecha_inscripcion', 'asistencia'];

    public function indicadores()
    {
        return $this->hasMany(ParticipanteIndicador::class, 'insc_participante_id');
    }

    public function planilla()
    {
        return $this->belongsTo(PlanillaInscripcion::class, 'planilla_id');
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }

    public function scopeAsistentes($query)
    {
        $rolAsistente = Rol::where('nombre', 'Asistente')->first();
        return $query->where('rol_id', $rolAsistente->rol_id ?? null);
    }

    public function scopeDisertantes($query)
    {
        $rolDisertante = Rol::where('nombre', 'Disertante')->first();
        return $query->where('rol_id', $rolDisertante->rol_id ?? null);
    }

    public function scopeColaboradores($query)
    {
        $rolColaborador = Rol::where('nombre', 'Colaborador')->first();
        return $query->where('rol_id', $rolColaborador->rol_id ?? null);
    }

    public function scopeDisertantesYColaboradores($query)
    {
        $rolesStaff = Rol::whereIn('nombre', ['Disertante', 'Colaborador'])->pluck('rol_id');
        return $query->whereIn('rol_id', $rolesStaff);
    }

    public function asistencias()
    {
        // Una inscripción puede tener muchas asistencias (una por sesión)
        return $this->hasMany(AsistenciaParticipante::class, 'inscripcion_participante_id');
    }
}
