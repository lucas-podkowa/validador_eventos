<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'rol';
    protected $primaryKey = 'rol_id';
    public $incrementing = true;

    protected $fillable = ['nombre'];

    public function eventoParticipantes()
    {
        return $this->hasMany(EventoParticipante::class, 'rol_id');
    }
}


/*
// Obtener el rol de un participante de evento
$eventoParticipante = EventoParticipante::find($id);
$rol = $eventoParticipante->rol;
echo $rol->nombre;

// Obtener todos los participantes con un rol específico
$rol = Rol::find($rolId);
$participantes = $rol->eventoParticipantes;

// Crear un participante con rol
EventoParticipante::create([
    'evento_id' => $eventoId,
    'participante_id' => $participanteId,
    'rol_id' => $rolId,
    // ... otros campos
]);

// Eager loading
$participantes = EventoParticipante::with('rol', 'evento', 'participante')->get();
*/