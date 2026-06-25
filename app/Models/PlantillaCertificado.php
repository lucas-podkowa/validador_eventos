<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaCertificado extends Model
{
    public $timestamps = false;

    protected $table = 'plantilla_certificado';

    protected $primaryKey = 'plantilla_id';

    public const TIPOS = ['asistencia', 'aprobacion', 'disertante', 'colaborador'];

    protected $fillable = ['categoria_id', 'nombre', 'imagen_path', 'tipo', 'por_defecto'];

    public function categoria()
    {
        return $this->belongsTo(CategoriaEvento::class, 'categoria_id');
    }

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorDefecto($query)
    {
        return $query->where('por_defecto', true);
    }
}
