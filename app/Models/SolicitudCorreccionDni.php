<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudCorreccionDni extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_RECHAZADA = 'rechazada';

    protected $table = 'solicitud_correccion_dni';

    protected $fillable = [
        'participante_id',
        'user_id',
        'dni_actual',
        'dni_solicitado',
        'motivo',
        'imagen_path',
        'imagen_mime',
        'imagen_original',
        'estado',
        'revisado_por',
        'revisado_en',
        'observacion',
    ];

    protected $casts = [
        'revisado_en' => 'datetime',
    ];

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id', 'participante_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por', 'id');
    }
}
