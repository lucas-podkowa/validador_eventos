<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class EventoParticipante extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'evento_participantes';
    protected $fillable = [
        'evento_participantes_id',
        'evento_id',
        'participante_id',
        'rol_id',
        'url',
        'qrcode',
        'aprobado',
        'emision_directa',
        'certificado_path'
    ];
    protected $primaryKey = 'evento_participantes_id';
    public $incrementing = false;
    protected $keyType = 'string';


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->evento_participantes_id)) {
                $model->evento_participantes_id = (string) Str::uuid();
            }
        });
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id');
    }
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }
}
