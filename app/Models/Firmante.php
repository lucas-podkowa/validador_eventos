<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firmante extends Model
{
    public $timestamps = false;

    protected $table = 'firmante';

    protected $primaryKey = 'firmante_id';

    protected $fillable = [
        'nombre',
        'cargo',
        'imagen_firma_path',
        'imagen_mime',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function contextos()
    {
        return $this->belongsToMany(Contexto::class, 'contexto_firmante', 'firmante_id', 'contexto_id')
            ->withPivot('orden', 'mostrar_cargo');
    }
}
