<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlanillaInscripcion extends Model
{

    use HasFactory;
    public $timestamps = false;
    protected $table = 'planilla_inscripcion';
    protected $primaryKey = 'planilla_inscripcion_id';
    public $incrementing = false; // Clave primaria no incrementa automáticamente
    protected $keyType = 'string'; // Tipo de clave primaria es string
    protected $fillable = ['planilla_inscripcion_id', 'apertura', 'cierre', 'evento_id', 'header', 'footer', 'qr_formulario', 'disposicion'];

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


    public function participantes()
    {
        return $this->belongsToMany(Participante::class, 'inscripcion_participante', 'planilla_id', 'participante_id')
            ->withPivot('fecha_inscripcion', 'asistencia');
    }
}
