<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    use HasFactory;
    protected $table = 'localidad';
    protected $primaryKey = 'localidad_id';

    // protected $fillable = ['nombre', 'pais_id'];
    protected $fillable = ['nombre'];

    // public function pais()
    // {
    //     return $this->belongsTo(Pais::class, 'pais_id');
    // }

    public function participantes()
    {
        return $this->hasMany(Participante::class, 'localidad_id');
    }
}
