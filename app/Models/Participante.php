<?php

namespace App\Models;

use App\Support\NormalizadorIdentidad;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Participante extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'participante';

    protected $primaryKey = 'participante_id';

    public $incrementing = false; // Clave primaria no incrementa automáticamente

    protected $keyType = 'string'; // Tipo de clave primaria es string

    protected $fillable = ['participante_id', 'nombre', 'apellido', 'dni', 'mail', 'telefono', 'user_id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->participante_id)) {
                $model->participante_id = (string) Str::uuid();
            }
        });
        static::saving(function ($model) {
            $model->nombre_norm = NormalizadorIdentidad::nombre($model->nombre);
            $model->apellido_norm = NormalizadorIdentidad::nombre($model->apellido);
            $model->telefono_norm = NormalizadorIdentidad::telefono($model->telefono);
            $model->mail_norm = NormalizadorIdentidad::mail($model->mail);
        });
    }

    /**
     * Nombre del participante: se guarda y se muestra en Title Case ("López Ricci").
     */
    protected function nombre(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => NormalizadorIdentidad::titulo($value),
            set: fn ($value) => NormalizadorIdentidad::titulo($value),
        );
    }

    /**
     * Apellido del participante: se guarda y se muestra en Title Case ("López Ricci").
     */
    protected function apellido(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => NormalizadorIdentidad::titulo($value),
            set: fn ($value) => NormalizadorIdentidad::titulo($value),
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
            ->withPivot('url', 'qrcode', 'rol_id', 'aprobado');
    }

    public function eventoParticipantes()
    {
        return $this->hasMany(EventoParticipante::class, 'participante_id', 'participante_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function duplicadosRevision()
    {
        return $this->hasMany(DuplicadoRevision::class, 'participante_id', 'participante_id');
    }

    public function solicitudesCorreccionDni()
    {
        return $this->hasMany(SolicitudCorreccionDni::class, 'participante_id', 'participante_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(InscripcionParticipante::class, 'participante_id', 'participante_id');
    }
}
