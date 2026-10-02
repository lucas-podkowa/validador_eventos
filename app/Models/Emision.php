<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Emision extends Model
{
    use HasFactory;

    public const ESTADO_EMITIDO = 'emitido';

    public const ESTADO_ANULADO = 'anulado';

    public const MATCH_AUTO = 'auto';

    public const MATCH_REVISAR = 'revisar';

    protected $table = 'emision';

    protected $primaryKey = 'emision_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'emision_id',
        'participante_id',
        'tipo_reconocimiento_id',
        'origen_type',
        'origen_id',
        'alcance',
        'plantilla_id',
        'codigo_verificacion',
        'qr',
        'certificado_path',
        'estado',
        'datos',
        'origen_snapshot',
        'texto_snapshot',
        'api_cliente_id',
        'external_ref',
        'match_estado',
        'match_detalle',
        'emitido_por',
        'emitida_en',
    ];

    protected $casts = [
        'datos' => 'array',
        'origen_snapshot' => 'array',
        'match_detalle' => 'array',
        'emitida_en' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->emision_id)) {
                $model->emision_id = (string) Str::uuid();
            }

            if (empty($model->codigo_verificacion)) {
                $model->codigo_verificacion = self::generarCodigo();
            }

            if (empty($model->emitida_en)) {
                $model->emitida_en = now();
            }
        });
    }

    public static function generarCodigo(): string
    {
        do {
            $codigo = Str::random(48);
        } while (self::where('codigo_verificacion', $codigo)->exists());

        return $codigo;
    }

    public function getRouteKeyName(): string
    {
        return 'emision_id';
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    public function tipoReconocimiento()
    {
        return $this->belongsTo(TipoReconocimiento::class, 'tipo_reconocimiento_id', 'tipo_reconocimiento_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(PlantillaCertificado::class, 'plantilla_id', 'plantilla_id');
    }

    public function origen()
    {
        return $this->morphTo('origen', 'origen_type', 'origen_id');
    }

    public function apiCliente()
    {
        return $this->belongsTo(ApiCliente::class, 'api_cliente_id', 'api_cliente_id');
    }

    public function emitidoPor()
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    public function esValida(): bool
    {
        return $this->estado === self::ESTADO_EMITIDO;
    }

    public function estaAnulada(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }

    public function scopeEmitidas($query)
    {
        return $query->where('estado', self::ESTADO_EMITIDO);
    }

    public function scopeAnuladas($query)
    {
        return $query->where('estado', self::ESTADO_ANULADO);
    }
}
