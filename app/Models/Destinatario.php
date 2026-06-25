<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destinatario extends Model
{
    public $timestamps = false;

    protected $table = 'destinatarios';

    protected $primaryKey = 'destinatario_id';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'evento_destinatario', 'destinatario_id', 'evento_id')
            ->withPivot('precio');
    }

    /**
     * Devuelve una versión visual del nombre en Title Case,
     * manteniendo en minúscula las preposiciones y conjunciones pequeñas.
     */
    public function getNombreDisplayAttribute()
    {
        $raw = $this->nombre ?? '';
        $small = [
            'de', 'del', 'la', 'el', 'las', 'los', 'y', 'en', 'para', 'con', 'por', 'a', 'al', 'o', 'su', 'sus', 'e',
        ];

        $parts = preg_split('/\s+/', mb_strtolower(trim($raw), 'UTF-8'));
        if (! $parts) {
            return '';
        }

        foreach ($parts as $i => $word) {
            if ($i > 0 && in_array($word, $small, true)) {
                $parts[$i] = $word; // keep lower
            } else {
                $parts[$i] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return implode(' ', $parts);
    }
}
