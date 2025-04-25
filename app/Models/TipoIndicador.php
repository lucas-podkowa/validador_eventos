<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoIndicador extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'tipo_indicador';
    protected $primaryKey = 'tipo_indicador_id';
    protected $fillable = ['nombre'];


    public function indicadores()
    {
        return $this->hasMany(Indicador::class, 'tipo_indicador_id');
    }

    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'evento_tipo_indicador', 'tipo_indicador_id', 'evento_id');
    }
}
