<?php

namespace App\Console\Commands;

use App\Models\ApiCliente;
use Illuminate\Console\Command;

class RevocarApiToken extends Command
{
    protected $signature = 'api:token:revocar
        {cliente : ID o nombre del cliente}
        {--id= : ID del token a revocar (si se omite, revoca todos los del cliente)}';

    protected $description = 'Revoca tokens Sanctum de un cliente de API';

    public function handle(): int
    {
        $cliente = $this->resolverCliente();

        if (! $cliente) {
            $this->error('No se encontró el cliente de API indicado.');

            return self::FAILURE;
        }

        $query = $cliente->tokens();

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        }

        $cantidad = $query->delete();

        if ($cantidad === 0) {
            $this->warn('No se revocó ningún token.');

            return self::SUCCESS;
        }

        $this->info("Tokens revocados: {$cantidad}");

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
