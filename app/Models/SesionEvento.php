<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionEvento extends Model
{
    use HasFactory;

    protected $table = 'sesion_evento'; // Nombre de la tabla
    protected $primaryKey = 'sesion_evento_id'; // Clave primaria
    public $timestamps = false; // Deshabilita timestamps

    protected $fillable = [
        'evento_id',
        'nombre',
        'fecha_hora_inicio',
        'fecha_hora_fin',
    ];

    // Relación con el evento
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id', 'evento_id');
    }


    // Relación con asistencia participante
    public function asistencias()
    {
        return $this->hasMany(AsistenciaParticipante::class, 'sesion_evento_id', 'sesion_evento_id');
    }
}
