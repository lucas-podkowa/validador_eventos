<?php

namespace App\Actions;

use App\Models\Participante;
use App\Support\NormalizadorIdentidad;

class BuscarParticipanteSimilar
{
    /**
     * Busca un participante existente con datos similares.
     * Coincide si el correo normalizado es igual (señal fuerte, el correo es único)
     * o si coinciden apellido + nombre + teléfono normalizados.
     * Excluye opcionalmente por DNI y/o participante_id (por ejemplo, el que se está editando).
     */
    public function buscar(
        ?string $apellido,
        ?string $nombre,
        ?string $telefono,
        ?string $mail = null,
        ?int $dniExcluir = null,
        ?string $participanteExcluir = null
    ): ?Participante {
        $apellidoNorm = NormalizadorIdentidad::nombre($apellido);
        $nombreNorm = NormalizadorIdentidad::nombre($nombre);
        $telefonoNorm = NormalizadorIdentidad::telefono($telefono);
        $mailNorm = NormalizadorIdentidad::mail($mail);

        $tieneNombreTelefono = $apellidoNorm !== '' && $nombreNorm !== '' && $telefonoNorm !== '';
        $tieneMail = $mailNorm !== '';

        if (! $tieneNombreTelefono && ! $tieneMail) {
            return null;
        }

        return Participante::query()
            ->where(function ($query) use ($tieneMail, $mailNorm, $tieneNombreTelefono, $apellidoNorm, $nombreNorm, $telefonoNorm) {
                if ($tieneMail) {
                    $query->orWhere('mail_norm', $mailNorm);
                }

                if ($tieneNombreTelefono) {
                    $query->orWhere(function ($sub) use ($apellidoNorm, $nombreNorm, $telefonoNorm) {
                        $sub->where('apellido_norm', $apellidoNorm)
                            ->where('nombre_norm', $nombreNorm)
                            ->where('telefono_norm', $telefonoNorm);
                    });
                }
            })
            ->when($dniExcluir, fn ($query) => $query->where('dni', '!=', $dniExcluir))
            ->when($participanteExcluir, fn ($query) => $query->where('participante_id', '!=', $participanteExcluir))
            ->orderByRaw(
                '(apellido_norm = ? AND nombre_norm = ? AND telefono_norm = ?) DESC',
                [$apellidoNorm, $nombreNorm, $telefonoNorm]
            )
            ->orderBy('participante_id')
            ->first();
    }
}
