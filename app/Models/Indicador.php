<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicador extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'indicador';
    protected $primaryKey = 'indicador_id';
    protected $fillable = ['nombre', 'descripcion', 'tipo_indicador_id'];



    public function tipoIndicador()
    {
        return $this->belongsTo(TipoIndicador::class, 'tipo_indicador_id');
    }

    public function inscripcionesParticipantes()
    {
        return $this->belongsToMany(InscripcionParticipante::class, 'participante_indicador', 'indicador_id', 'insc_participante_id');
    }

    // Verificamos si hay inscripciones asociadas al indicador
    public function hasInscripciones()
    {
        return $this->inscripcionesParticipantes()->exists();
    }
}
