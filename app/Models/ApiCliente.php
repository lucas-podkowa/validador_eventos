<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class ApiCliente extends Model
{
    use HasApiTokens;

    protected $table = 'api_cliente';

    protected $primaryKey = 'api_cliente_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'scopes',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'scopes' => 'array',
    ];

    public function certificadosExternos()
    {
        return $this->hasMany(CertificadoExterno::class, 'api_cliente_id', 'api_cliente_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
