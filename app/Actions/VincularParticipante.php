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

    /**
     * Vincula un participante sin cuenta con su usuario existente, exigiendo que
     * coincidan el correo del participante con el de la cuenta y el DNI de ambos.
     * Se usa al emitir certificados de sistemas externos para que el usuario vea
     * el certificado al iniciar sesión. No hace nada si ya está vinculado.
     */
    public function vincularParticipanteExistente(Participante $participante): ?User
    {
        if ($participante->user_id) {
            return $participante->user;
        }

        $dni = trim((string) $participante->dni);
        $email = $this->normalizar($participante->mail);

        if ($dni === '' || $email === '') {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->get()
            ->first(fn (User $candidato) => trim((string) $candidato->dni) === $dni);

        if (! $user) {
            return null;
        }

        if (Participante::where('user_id', $user->id)->exists()) {
            return null;
        }

        $participante->user_id = $user->id;
        $participante->save();

        return $user;
    }

    protected function normalizar(?string $valor): string
    {
        return mb_strtolower(trim((string) $valor));
    }
}
