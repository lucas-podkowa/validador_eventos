<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEvento extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'tipo_evento';
    protected $primaryKey = 'tipo_evento_id';


    protected $fillable = ['nombre'];

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'tipo_evento_id');
    }
}
