<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TituloIntermedio extends Model
{
    use HasFactory;

    protected $table = 'titulo_intermedio';

    protected $fillable = ['carrera_id', 'nombre', 'activo', 'imagen_plantilla_path'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function certificadosEmitidos()
    {
        return $this->hasMany(CertificadoEmitido::class, 'titulo_intermedio_id');
    }
}
