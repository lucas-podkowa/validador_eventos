<?php

namespace App\Console\Commands;

use App\Models\ApiCliente;
use Illuminate\Console\Command;

class CrearApiToken extends Command
{
    protected $signature = 'api:token:crear
        {cliente : ID o nombre del cliente}
        {--ability=* : Habilidad a otorgar (puede repetirse, por defecto certificados:emitir)}
        {--name= : Nombre del token}';

    protected $description = 'Genera un token Sanctum para un cliente de API (se muestra una sola vez)';

    public function handle(): int
    {
        $cliente = $this->resolverCliente();

        if (! $cliente) {
            $this->error('No se encontró el cliente de API indicado.');

            return self::FAILURE;
        }

        $abilities = $this->option('ability') ?: ['certificados:emitir'];
        $name = $this->option('name') ?: 'token-'.now()->format('YmdHis');

        $token = $cliente->createToken($name, $abilities);

        $this->newLine();
        $this->warn('Copiá el token ahora: no se volverá a mostrar.');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->info("Cliente: {$cliente->nombre} (#{$cliente->api_cliente_id})");
        $this->info('Habilidades: '.implode(', ', $abilities));

        return self::SUCCESS;
    }

    private function resolverCliente(): ?ApiCliente
    {
        $valor = (string) $this->argument('cliente');

        if (ctype_digit($valor)) {
            $cliente = ApiCliente::find((int) $valor);

            if ($cliente) {
                return $cliente;
            }
        }

        return ApiCliente::where('nombre', $valor)->first();
    }
}
