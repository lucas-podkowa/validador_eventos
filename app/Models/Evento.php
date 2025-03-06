<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Evento extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'evento';
    protected $primaryKey = 'evento_id';
    public $incrementing = false; // Clave primaria no incrementa automáticamente
    protected $keyType = 'string'; // Tipo de clave primaria es string

    protected $fillable = ['evento_id', 'nombre', 'lugar', 'fecha_inicio', 'tipo_evento_id'];

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
    // Acceder a los participantes inscriptos a través de la planilla de inscripción
    public function inscriptos()
    {
        return $this->hasManyThrough(
            Participante::class,
            InscripcionParticipante::class,
            'planilla_id', // Clave foránea en InscripcionParticipante
            'participante_id', // Clave foránea en Participante
            'evento_id', // Clave primaria en Evento
            'participante_id' // Clave en InscripcionParticipante
        )->withPivot('fecha_inscripcion', 'asistencia');
    }


    public function participantes()
    {
        return $this->belongsToMany(Participante::class, 'evento_participantes', 'evento_id', 'participante_id')
            ->withPivot('url', 'qrcode');
    }



    public function getFechaInicioFormattedAttribute()
    {
        return Carbon::parse($this->fecha_inicio)->format('d-m-Y');
    }
}
