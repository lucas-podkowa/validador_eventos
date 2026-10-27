<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Participacion extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $recordEvents = ['updated', 'deleted'];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EMITIDO = 'emitido';

    public const ESTADO_ANULADO = 'anulado';

    protected $table = 'participacion';

    protected $primaryKey = 'participacion_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'participacion_id',
        'participante_id',
        'origen_type',
        'origen_id',
        'tipo_reconocimiento_id',
        'aprobado',
        'estado',
        'emision_id',
    ];

    protected $casts = [
        'aprobado' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['participante_id', 'origen_type', 'origen_id', 'tipo_reconocimiento_id', 'aprobado', 'estado', 'emision_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('participacion')
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'updated' => 'Participación actualizada',
                'deleted' => 'Participación eliminada',
                default => "Participación: {$eventName}",
            });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->participacion_id)) {
                $model->participacion_id = (string) Str::uuid();
            }
        });
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    public function tipoReconocimiento()
    {
        return $this->belongsTo(TipoReconocimiento::class, 'tipo_reconocimiento_id', 'tipo_reconocimiento_id');
    }

    public function emision()
    {
        return $this->belongsTo(Emision::class, 'emision_id', 'emision_id');
    }

    public function origen()
    {
        return $this->morphTo('origen', 'origen_type', 'origen_id');
    }

    public function estaEmitida(): bool
    {
        return $this->estado === self::ESTADO_EMITIDO;
    }
}
