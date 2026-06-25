<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitoDocumentacion extends Model
{
    public $timestamps = false;

    protected $table = 'requisitos_documentacion';

    protected $primaryKey = 'requisito_id';

    protected $fillable = [
        'evento_id',
        'destinatario_id',
        'titulo',
        'orden',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id', 'evento_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(Destinatario::class, 'destinatario_id', 'destinatario_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoPresentado::class, 'requisito_id', 'requisito_id');
    }
}
