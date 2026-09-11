<?php

namespace App\Actions;

use App\Models\Participante;
use App\Models\User;

class VincularParticipante
{
    /**
     * Vincula la cuenta con un participante existente solo si coinciden el DNI
     * y el correo electrónico, y el participante todavía no tiene cuenta.
     */
    public function vincular(User $user): ?Participante
    {
        $dni = trim((string) $user->dni);
        $email = $this->normalizar($user->email);

        if ($dni === '' || $email === '') {
            return null;
        }

        $participante = Participante::whereNull('user_id')
            ->where('dni', $dni)
            ->get()
            ->first(fn (Participante $p) => $this->normalizar($p->mail) === $email);

        if (! $participante) {
            return null;
        }

        $participante->user_id = $user->id;
        $participante->save();

        return $participante;
    }

    protected function normalizar(?string $valor): string
    {
        return mb_strtolower(trim((string) $valor));
    }
}
