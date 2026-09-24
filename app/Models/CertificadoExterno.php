<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CertificadoExterno extends Model
{
    use HasFactory;

    public const ESTADO_EMITIDO = 'emitido';

    public const ESTADO_ANULADO = 'anulado';

    public const MATCH_AUTO = 'auto';

    public const MATCH_REVISAR = 'revisar';

    protected $table = 'certificado_externo';

    protected $primaryKey = 'certificado_externo_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'certificado_externo_id',
        'api_cliente_id',
        'external_ref',
        'tipo',
        'plantilla_certificado_id',
        'contexto_id',
        'participante_id',
        'datos',
        'receptor_nombre',
        'receptor_dni',
        'receptor_email',
        'match_estado',
        'match_detalle',
        'certificado_path',
        'qrcode',
        'codigo_verificacion',
        'estado',
    ];

    protected $casts = [
        'datos' => 'array',
        'match_detalle' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->certificado_externo_id)) {
                $model->certificado_externo_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'certificado_externo_id';
    }

    public function apiCliente()
    {
        return $this->belongsTo(ApiCliente::class, 'api_cliente_id', 'api_cliente_id');
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(PlantillaCertificado::class, 'plantilla_certificado_id', 'plantilla_id');
    }

    public function contexto()
    {
        return $this->belongsTo(Contexto::class, 'contexto_id', 'contexto_id');
    }

    public function requiereRevision(): bool
    {
        return $this->match_estado === self::MATCH_REVISAR;
    }

    public function scopeEmitidos($query)
    {
        return $query->where('estado', self::ESTADO_EMITIDO);
    }

    public function scopeRevision($query)
    {
        return $query->where('match_estado', self::MATCH_REVISAR);
    }
}
