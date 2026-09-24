<?php

namespace App\Console\Commands;

use App\Models\ApiCliente;
use Illuminate\Console\Command;

class CrearApiCliente extends Command
{
    protected $signature = 'api:cliente:crear
        {nombre : Nombre del sistema cliente}
        {--descripcion= : Descripción opcional}';

    protected $description = 'Crea un cliente de API (por ejemplo, PPS) para emitir certificados externos';

    public function handle(): int
    {
        $cliente = ApiCliente::create([
            'nombre' => $this->argument('nombre'),
            'descripcion' => $this->option('descripcion') ?: null,
            'activo' => true,
        ]);

        $this->info("Cliente API #{$cliente->api_cliente_id} creado: {$cliente->nombre}");
        $this->line('Generá un token con: php artisan api:token:crear '.$cliente->api_cliente_id);

        return self::SUCCESS;
    }
}
