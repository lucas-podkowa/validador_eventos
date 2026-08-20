<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificadoEmitido extends Model
{
    use HasFactory;

    protected $table = 'certificado_emitido';

    protected $fillable = [
        'participante_id',
        'titulo_intermedio_id',
        'certificado_path',
        'anulado',
        'emitido_por',
    ];

    protected $casts = [
        'anulado' => 'boolean',
    ];

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    public function tituloIntermedio()
    {
        return $this->belongsTo(TituloIntermedio::class, 'titulo_intermedio_id');
    }

    public function emitidoPor()
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }
}
