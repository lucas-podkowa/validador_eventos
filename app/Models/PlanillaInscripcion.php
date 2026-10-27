<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PlanillaInscripcion extends Model
{
    use HasFactory;
    use LogsActivity;

    public $timestamps = false;

    protected $table = 'planilla_inscripcion';

    protected $primaryKey = 'planilla_inscripcion_id';

    public $incrementing = false; // Clave primaria no incrementa automáticamente

    protected $keyType = 'string'; // Tipo de clave primaria es string

    protected $fillable = ['planilla_inscripcion_id', 'apertura', 'cierre', 'evento_id', 'header', 'footer', 'qr_formulario', 'disposicion', 'nro_expediente', 'estado'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['evento_id', 'apertura', 'cierre', 'disposicion', 'nro_expediente', 'estado'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('planilla')
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Planilla de inscripción creada',
                'updated' => 'Planilla de inscripción actualizada',
                'deleted' => 'Planilla de inscripción eliminada',
                default => "Planilla: {$eventName}",
            });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->planilla_inscripcion_id)) {
                $model->planilla_inscripcion_id = (string) Str::uuid();
            }
        });
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(InscripcionParticipante::class, 'planilla_id');
    }

    public function inscripcionesParticipantes()
    {
        return $this->hasMany(InscripcionParticipante::class, 'planilla_id')
            ->participantes()
            ->with('participante');
    }

    public function inscripcionesDisertantes()
    {
        return $this->hasMany(InscripcionParticipante::class, 'planilla_id')
            ->disertantes()
            ->with('participante');
    }

    public function inscripcionesColaboradores()
    {
        return $this->hasMany(InscripcionParticipante::class, 'planilla_id')
            ->colaboradores()
            ->with('participante');
    }

    public function inscripcionesDisertantesYColaboradores()
    {
        return $this->hasMany(InscripcionParticipante::class, 'planilla_id')
            ->disertantesYColaboradores()
            ->with('participante');
    }

    public function participantes()
    {
        return $this->belongsToMany(Participante::class, 'inscripcion_participante', 'planilla_id', 'participante_id')
            ->withPivot('fecha_inscripcion', 'asistencia');
    }
}
