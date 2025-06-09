<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Evento extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'evento';
    protected $primaryKey = 'evento_id';
    public $incrementing = false; // Clave primaria no incrementa automáticamente
    protected $keyType = 'string'; // Tipo de clave primaria es string

    protected $fillable = ['evento_id', 'nombre', 'lugar', 'fecha_inicio', 'tipo_evento_id', 'certificado_path', 'cupo', 'por_aprobacion', 'revisor_id', 'revisado'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->evento_id)) {
                $model->evento_id = (string) Str::uuid();
            }
        });
    }

    public function tipoEvento()
    {
        return $this->belongsTo(TipoEvento::class, 'tipo_evento_id');
    }

    public function tipoIndicadores()
    {
        return $this->belongsToMany(TipoIndicador::class, 'evento_tipo_indicador', 'evento_id', 'tipo_indicador_id');
    }

    public function planillaInscripcion()
    {
        return $this->hasOne(PlanillaInscripcion::class, 'evento_id');
    }

    public function esPorAprobacion(): bool
    {
        return $this->por_aprobacion;
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function participantes()
    {
        return $this->belongsToMany(Participante::class, 'evento_participantes', 'evento_id', 'participante_id')
            ->withPivot('url', 'qrcode', 'aprobado');
    }


    public function sesiones()
    {
        return $this->hasMany(SesionEvento::class, 'evento_id', 'evento_id');
    }

    // Evento → Planilla → InscripcionParticipante → Participante
    public function inscriptos()
    {
        $planilla = $this->planillaInscripcion;

        if (!$planilla) {
            return collect(); // si no hay planilla, entonces no hay inscriptos
        }

        return Participante::whereIn('participante_id', function ($query) use ($planilla) {
            $query->select('participante_id')
                ->from('inscripcion_participante')
                ->where('planilla_id', $planilla->planilla_inscripcion_id);
        })->get();
    }


    public function tieneAsistencias(): bool
    {
        return AsistenciaParticipante::whereHas('sesionEvento', function ($query) {
            $query->where('evento_id', $this->evento_id);
        })->where('asistio', true)->exists();
    }

    public function participantesConAsistencia()
    {
        return Participante::whereIn('participante_id', function ($query) {
            $query->select('participante_id')
                ->from('asistencia_participante')
                ->where('asistio', true)
                ->whereIn('sesion_evento_id', function ($subquery) {
                    $subquery->select('sesion_evento_id')
                        ->from('sesion_evento')
                        ->where('evento_id', $this->evento_id);
                });
        })->get();
    }


    public function getFechaInicioFormattedAttribute()
    {
        return Carbon::parse($this->fecha_inicio)->format('d/m/Y');
        //     return Carbon::parse($this->fecha_inicio)->format('d/m/Y H:i');
    }
}
