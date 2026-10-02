<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoReconocimiento extends Model
{
    use HasFactory;

    public const ALCANCE_EVENTO = 'evento';

    public const ALCANCE_CONTEXTO = 'contexto';

    public const ALCANCE_PROGRAMA = 'programa';

    public const ALCANCE_SIN_ORIGEN = 'sin_origen';

    public const ALCANCES = [
        self::ALCANCE_EVENTO,
        self::ALCANCE_CONTEXTO,
        self::ALCANCE_PROGRAMA,
        self::ALCANCE_SIN_ORIGEN,
    ];

    /**
     * Alias aceptados en la API externa para no romper integraciones previas.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'tutor_academico' => 'tutor',
    ];

    public $timestamps = false;

    protected $table = 'tipo_reconocimiento';

    protected $primaryKey = 'tipo_reconocimiento_id';

    protected $fillable = ['nombre', 'slug', 'alcance_sugerido', 'es_sistema', 'activo', 'orden'];

    protected $casts = [
        'es_sistema' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function plantillas()
    {
        return $this->hasMany(PlantillaCertificado::class, 'tipo_reconocimiento_id', 'tipo_reconocimiento_id');
    }

    public function emisiones()
    {
        return $this->hasMany(Emision::class, 'tipo_reconocimiento_id', 'tipo_reconocimiento_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Normaliza un slug o alias recibido desde la API al slug canónico.
     */
    public static function slugDesdeAlias(?string $valor): string
    {
        $valor = trim((string) $valor);

        return self::ALIASES[$valor] ?? $valor;
    }
}
