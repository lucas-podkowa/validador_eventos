<?php

namespace App\Console\Commands;

use App\Models\Participante;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VincularParticipantesUsuarios extends Command
{
    /**
     * @var string
     */
    protected $signature = 'participantes:vincular-usuarios
        {--dry-run : Muestra qué vínculos se crearían sin modificar la base}
        {--solo-invitados : Solo considera usuarios con rol Invitado}';

    /**
     * @var string
     */
    protected $description = 'Vincula participantes sin cuenta con usuarios existentes por correo (y verifica DNI) de forma no destructiva';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $soloInvitados = (bool) $this->option('solo-invitados');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: no se guardará ningún cambio.');
        }

        $vinculados = 0;
        $sinUsuario = 0;
        $conflictos = 0;
        $revisados = 0;

        Participante::whereNull('user_id')->orderBy('apellido')->chunk(200, function ($participantes) use (
            $dryRun,
            $soloInvitados,
            &$vinculados,
            &$sinUsuario,
            &$conflictos,
            &$revisados
        ) {
            foreach ($participantes as $participante) {
                $revisados++;
                $mail = mb_strtolower(trim((string) $participante->mail));

                $candidatos = User::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$mail])
                    ->when($soloInvitados, function ($query) {
                        $query->whereHas('roles', fn ($roles) => $roles->where('name', 'Invitado'));
                    })
                    ->get();

                if ($candidatos->isEmpty()) {
                    $sinUsuario++;

                    continue;
                }

                $user = $candidatos->first();

                if (Participante::where('user_id', $user->id)->exists()) {
                    $this->warn("Conflicto: el usuario {$user->email} ya está vinculado a otro participante. Se omite DNI {$participante->dni}.");
                    $conflictos++;

                    continue;
                }

                if ($user->dni !== null && (string) $user->dni !== '' && (string) $user->dni !== (string) $participante->dni) {
                    $this->warn("Conflicto: el DNI del usuario {$user->email} ({$user->dni}) no coincide con el del participante ({$participante->dni}). Se omite.");
                    $conflictos++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("[dry-run] Vincularía participante DNI {$participante->dni} ({$participante->mail}) con usuario {$user->email}");
                    $vinculados++;

                    continue;
                }

                DB::transaction(function () use ($participante, $user) {
                    if ($user->dni === null || (string) $user->dni === '') {
                        $user->dni = $participante->dni;
                        $user->save();
                    }

                    $participante->user_id = $user->id;
                    $participante->save();
                });

                $vinculados++;
            }
        });

        $this->newLine();
        $this->info("Revisados: {$revisados}");
        $this->info(($dryRun ? 'Vínculos detectados: ' : 'Vínculos creados: ').$vinculados);
        $this->info("Participantes sin usuario: {$sinUsuario}");
        $this->info("Conflictos omitidos: {$conflictos}");

        return self::SUCCESS;
    }
}
