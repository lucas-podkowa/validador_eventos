<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Participante extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'participante';
    protected $primaryKey = 'participante_id';
    public $incrementing = false; // Clave primaria no incrementa automáticamente
    protected $keyType = 'string'; // Tipo de clave primaria es string

    protected $fillable = ['participante_id', 'nombre', 'apellido', 'dni', 'mail', 'telefono'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->participante_id)) {
                $model->participante_id = (string) Str::uuid();
            }
        });
    }
    /**
     * Obtiene el nombre del participante formateado.
     */
    protected function nombre(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_convert_case(mb_strtolower(trim($value)), MB_CASE_TITLE, "UTF-8")
        );
    }

    /**
     * Obtiene el apellido del participante formateado.
     */
    protected function apellido(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_convert_case(mb_strtolower(trim($value)), MB_CASE_TITLE, "UTF-8")
        );
    }

    public function planillasInscripcion()
    {
        return $this->belongsToMany(PlanillaInscripcion::class, 'inscripcion_participante', 'participante_id', 'planilla_id')
            ->withPivot('fecha_inscripcion', 'asistencia')->withTimestamps();
    }

    public function indicadores()
    {
        return $this->belongsToMany(Indicador::class, 'participante_indicador', 'participante_id', 'indicador_id');
    }

    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'evento_participantes', 'participante_id', 'evento_id')
            ->withPivot('url', 'qrcode')->withTimestamps();
    }
}
