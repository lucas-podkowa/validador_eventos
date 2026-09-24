<?php

namespace App\Services;

use App\Actions\BuscarParticipanteSimilar;
use App\Actions\VincularParticipante;
use App\Models\Participante;
use App\Support\NormalizadorIdentidad;

/**
 * Resuelve a qué participante pertenece un tutor enviado por un sistema externo
 * (PPS) y con qué nivel de confianza, aplicando una cascada de claves.
 *
 * Tiers:
 *  1: DNI + mail                 -> auto    (vincula cuenta si User coincide en DNI+mail)
 *  2: DNI + teléfono             -> revisar
 *  3: DNI + apellido + teléfono  -> auto    (vincula cuenta si User coincide en DNI+mail)
 *  4: solo DNI                   -> revisar
 *  5: sin DNI, mail exacto       -> revisar
 *  6: sin DNI, teléfono + apellido -> revisar
 *  7: sin match                  -> auto    (crea participante)
 */
class ResolverParticipanteExterno
{
    public const AUTO = 'auto';

    public const REVISAR = 'revisar';

    private string $mailNorm = '';

    private string $telefonoNorm = '';

    private string $mailRaw = '';

    public function __construct(
        private BuscarParticipanteSimilar $buscarSimilar,
        private VincularParticipante $vincularParticipante,
    ) {}

    /**
     * @param  array{apellido?:string, nombres?:string, nombre?:string, dni?:string|int, email?:string, mail?:string, telefono?:string}  $tutor
     * @return array{participante: Participante, match_estado: string, match_detalle: array<string, mixed>}
     */
    public function resolver(array $tutor): array
    {
        $dni = preg_replace('/\D+/', '', (string) ($tutor['dni'] ?? '')) ?? '';
        $this->mailRaw = (string) ($tutor['email'] ?? ($tutor['mail'] ?? ''));
        $this->mailNorm = NormalizadorIdentidad::mail($this->mailRaw);
        $this->telefonoNorm = NormalizadorIdentidad::telefono($tutor['telefono'] ?? null);
        $apellidoNorm = NormalizadorIdentidad::nombre($tutor['apellido'] ?? null);

        if ($dni !== '') {
            $participante = Participante::where('dni', $dni)->first();

            if ($participante) {
                if ($this->mailNorm !== '' && NormalizadorIdentidad::mail($participante->mail) === $this->mailNorm) {
                    return $this->resultado($participante, self::AUTO, 1, [
                        'dni' => $dni, 'mail' => $this->mailNorm,
                    ]);
                }

                if ($this->telefonoNorm !== '' && NormalizadorIdentidad::telefono($participante->telefono) === $this->telefonoNorm) {
                    $mismoApellido = $apellidoNorm !== ''
                        && NormalizadorIdentidad::nombre($participante->apellido) === $apellidoNorm;

                    return $mismoApellido
                        ? $this->resultado($participante, self::AUTO, 3, [
                            'dni' => $dni, 'telefono' => $this->telefonoNorm, 'apellido' => $apellidoNorm,
                        ])
                        : $this->resultado($participante, self::REVISAR, 2, [
                            'dni' => $dni, 'telefono' => $this->telefonoNorm, 'motivo' => 'dni_telefono_sin_apellido',
                        ]);
                }

                return $this->resultado($participante, self::REVISAR, 4, [
                    'dni' => $dni, 'motivo' => 'solo_dni',
                ]);
            }
        }

        if ($this->mailNorm !== '') {
            $porMail = Participante::where('mail_norm', $this->mailNorm)->first();

            if ($porMail) {
                return $this->resultado($porMail, self::REVISAR, 5, [
                    'dni_payload' => $dni, 'mail' => $this->mailNorm, 'dni_registrado' => (string) $porMail->dni,
                    'motivo' => 'mail_existente_dni_distinto',
                ]);
            }
        }

        if ($this->telefonoNorm !== '' && $apellidoNorm !== '') {
            $similar = $this->buscarSimilar->buscar(
                $tutor['apellido'] ?? null,
                $tutor['nombres'] ?? ($tutor['nombre'] ?? null),
                $tutor['telefono'] ?? null,
            );

            if ($similar) {
                return $this->resultado($similar, self::REVISAR, 6, [
                    'telefono' => $this->telefonoNorm, 'apellido' => $apellidoNorm,
                    'motivo' => 'telefono_apellido_sin_dni',
                ]);
            }
        }

        return $this->crear($tutor, $dni);
    }

    /**
     * @return array{participante: Participante, match_estado: string, match_detalle: array<string, mixed>}
     */
    private function resultado(Participante $participante, string $estado, int $tier, array $detalle): array
    {
        $this->completarDatosFaltantes($participante);

        if ($estado === self::AUTO) {
            $this->vincularParticipante->vincularParticipanteExistente($participante);
            $participante->refresh();
        }

        return [
            'participante' => $participante,
            'match_estado' => $estado,
            'match_detalle' => array_merge(['tier' => $tier], $detalle),
        ];
    }

    /**
     * @return array{participante: Participante, match_estado: string, match_detalle: array<string, mixed>}
     */
    private function crear(array $tutor, string $dni): array
    {
        $participante = Participante::create([
            'apellido' => (string) ($tutor['apellido'] ?? ''),
            'nombre' => (string) ($tutor['nombres'] ?? ($tutor['nombre'] ?? '')),
            'dni' => (int) $dni,
            'mail' => $this->mailRaw,
            'telefono' => (string) ($tutor['telefono'] ?? ''),
        ]);

        $this->vincularParticipante->vincularParticipanteExistente($participante);
        $participante->refresh();

        return [
            'participante' => $participante,
            'match_estado' => self::AUTO,
            'match_detalle' => ['tier' => 7, 'dni' => $dni, 'mail' => $this->mailNorm, 'telefono' => $this->telefonoNorm],
        ];
    }

    /**
     * Completa mail/teléfono del participante solo si estaban vacíos. Nunca sobrescribe
     * datos existentes ni pisa un correo que ya pertenezca a otro participante.
     */
    private function completarDatosFaltantes(Participante $participante): void
    {
        $cambios = [];

        if (trim((string) $participante->mail) === '' && $this->mailRaw !== ''
            && ! Participante::where('mail_norm', $this->mailNorm)
                ->where('participante_id', '!=', $participante->participante_id)
                ->exists()) {
            $cambios['mail'] = $this->mailRaw;
        }

        if (trim((string) $participante->telefono) === '' && $this->telefonoNorm !== '') {
            $cambios['telefono'] = $this->telefonoNorm;
        }

        if ($cambios !== []) {
            $participante->fill($cambios)->save();
            $participante->refresh();
        }
    }
}
