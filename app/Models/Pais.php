<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    use HasFactory;
    protected $table = 'pais';
    protected $primaryKey = 'pais_id';

    protected $fillable = ['nombre'];

    // public function localidades()
    // {
    //     return $this->hasMany(Localidad::class, 'pais_id');
    // }
}
