<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    public function gestores()
    {
        return $this->belongsToMany(User::class, 'evento_gestor', 'evento_id', 'user_id');
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
            ->withPivot('url', 'qrcode', 'aprobado', 'rol_id');
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



    public function inscripcionesConAsistencia() // Renombramos para claridad
    {
        $planillaId = $this->planillaInscripcion?->planilla_inscripcion_id;

        if (!$planillaId) {
            return collect();
        }

        return InscripcionParticipante::where('planilla_id', $planillaId)
            // La inscripción debe tener al menos una asistencia registrada y confirmada.
            ->whereHas('asistencias', function ($queryAsistencia) {
                $queryAsistencia->where('asistio', true)
                    // La asistencia debe corresponder a una sesión de este evento.
                    ->whereHas('sesionEvento', function ($querySesion) {
                        $querySesion->where('evento_id', $this->evento_id);
                    });
            })
            // Opcional: Cargar el Participante si aún lo necesitas para acceder a sus datos
            //->with('participante')
            ->get();
    }


    public function inscripcionesFinales()
    {
        $planillaId = $this->planillaInscripcion?->planilla_inscripcion_id;

        if (!$planillaId) {
            return collect();
        }

        // Obtener los IDs de los roles de staff
        $rolesStaffIds = Rol::whereIn('nombre', ['Disertante', 'Colaborador'])->pluck('rol_id')->toArray();

        return InscripcionParticipante::where('planilla_id', $planillaId)
            ->where(function (Builder $query) use ($rolesStaffIds) {

                // CRITERIO A: Asistentes con asistencia registrada
                $query->whereHas('asistencias', function ($queryAsistencia) {
                    $queryAsistencia->where('asistio', true)
                        // La asistencia debe corresponder a una sesión de este evento.
                        ->whereHas('sesionEvento', function ($querySesion) {
                            $querySesion->where('evento_id', $this->evento_id);
                        });
                })

                    // CRITERIO B: Disertantes o Colaboradores (sin importar la asistencia)
                    ->orWhereIn('rol_id', $rolesStaffIds);
            })
            ->get();
    }

    /**
     * Obtener solo inscriptos con rol "Asistente"
     */
    public function asistentesInscritos()
    {
        $planilla = $this->planillaInscripcion;

        if (!$planilla) {
            return collect();
        }

        $rolAsistente = Rol::where('nombre', 'Asistente')->first();

        return Participante::whereIn('participante_id', function ($query) use ($planilla, $rolAsistente) {
            $query->select('participante_id')
                ->from('inscripcion_participante')
                ->where('planilla_id', $planilla->planilla_inscripcion_id)
                ->where('rol_id', $rolAsistente->rol_id ?? null);
        })->get();
    }

    /**
     * Obtener disertantes y colaboradores
     */
    public function disentantesYColaboradores()
    {
        $planilla = $this->planillaInscripcion;

        if (!$planilla) {
            return collect();
        }

        $roles = Rol::whereIn('nombre', ['Disertante', 'Colaborador'])->pluck('rol_id');

        return Participante::whereIn('participante_id', function ($query) use ($planilla, $roles) {
            $query->select('participante_id')
                ->from('inscripcion_participante')
                ->where('planilla_id', $planilla->planilla_inscripcion_id)
                ->whereIn('rol_id', $roles);
        })->get();
    }

    //uso en Livewire
    //$asistentes = $evento->asistentesInscritos();
    //$disertantesYColaboradores = $evento->disentantesYColaboradores();

    public function getFechaInicioFormattedAttribute()
    {
        return Carbon::parse($this->fecha_inicio)->format('d/m/Y');
        //     return Carbon::parse($this->fecha_inicio)->format('d/m/Y H:i');
    }
}
