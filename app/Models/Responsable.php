<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Responsable extends Model
{
    public $timestamps = false;
    protected $table = 'responsable';
    protected $primaryKey = 'responsable_id';

    protected $fillable = ['nombre', 'apellido', 'dni'];

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'responsable_id');
    }

    // Accessors para formatear nombre y apellido en Title Case
    public function getNombreAttribute($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public function getApellidoAttribute($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
