<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateFreshKeepParticipante extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-fresh-keep-participante';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Respaldando tabla participante...');
        $participantes = DB::table('participante')->get();

        $this->call('migrate:fresh', ['--seed' => true]);

        $this->info('Restaurando datos de participante...');
        foreach ($participantes as $p) {
            DB::table('participante')->insert((array) $p);
        }

        $this->info('Proceso completado con éxito.');
    }
}
