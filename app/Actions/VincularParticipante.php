<?php

namespace App\Actions;

use App\Models\Participante;
use App\Models\User;

class VincularParticipante
{
    /**
     * Vincula la cuenta con un participante existente usando el DNI propio de la cuenta.
     */
    public function vincular(User $user): ?Participante
    {
        return $this->vincularConDni($user, (string) $user->dni);
    }

    /**
     * Vincula la cuenta con un participante existente si coinciden el DNI y el correo,
     * y el participante todavía no tiene cuenta.
     */
    public function vincularConDni(User $user, string $dni): ?Participante
    {
        $dni = trim($dni);
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

        if (empty($user->dni)) {
            $user->dni = $participante->dni;
            $user->save();
        }

        return $participante;
    }

    protected function normalizar(?string $valor): string
    {
        return mb_strtolower(trim((string) $valor));
    }
}
