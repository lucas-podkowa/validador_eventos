<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuplicadoRevision extends Model
{
    protected $table = 'duplicado_revision';

    protected $fillable = [
        'participante_id',
        'candidato_id',
        'origen',
        'decision',
        'detalle',
        'revisado',
    ];

    protected $casts = [
        'revisado' => 'boolean',
    ];

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    public function candidato()
    {
        return $this->belongsTo(Participante::class, 'candidato_id', 'participante_id');
    }
}
