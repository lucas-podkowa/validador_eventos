<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipanteIndicador extends Model
{
    protected $table = 'participante_indicador';
    protected $primaryKey = 'participante_indicador_id';
    public $timestamps = false;

    protected $fillable = [
        'insc_participante_id',
        'indicador_id',
    ];

    public function indicador()
    {
        return $this->belongsTo(Indicador::class, 'indicador_id');
    }
}
