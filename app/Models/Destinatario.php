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
}
