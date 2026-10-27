<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ApiCliente extends Model
{
    use HasApiTokens;
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'descripcion', 'activo', 'scopes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('api_cliente')
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Cliente de API creado',
                'updated' => 'Cliente de API actualizado',
                'deleted' => 'Cliente de API eliminado',
                default => "Cliente de API: {$eventName}",
            });
    }

    public function emisiones()
    {
        return $this->hasMany(Emision::class, 'api_cliente_id', 'api_cliente_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
