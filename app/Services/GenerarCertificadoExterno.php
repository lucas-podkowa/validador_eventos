<?php

namespace App\Services;

use App\Models\ApiCliente;
use App\Models\Contexto;
use App\Models\Emision;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use RuntimeException;

/**
 * Emite un certificado externo (por ejemplo, tutores académicos de PPS) delegando en
 * el emisor unificado. La emisión queda en `emision` y se valida por su código.
 */
class GenerarCertificadoExterno
{
    public function __construct(
        private ResolverParticipanteExterno $resolver,
        private GenerarCertificadoEmision $emisor,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function emitir(ApiCliente $cliente, array $datos): Emision
    {
        $externalRef = (string) ($datos['external_ref'] ?? '');

        if ($externalRef !== '') {
            $existente = Emision::query()
                ->where('api_cliente_id', $cliente->api_cliente_id)
                ->where('external_ref', $externalRef)
                ->first();

            if ($existente) {
                return $existente;
            }
        }

        $tutor = $datos['tutor'] ?? [];
        $practica = $datos['practica'] ?? [];

        $match = $this->resolver->resolver($tutor);
        $tipo = $this->resolverTipo($datos);
        $plantilla = $this->resolverPlantilla($datos, $tipo);

        if (! $plantilla) {
            throw new RuntimeException('No hay una plantilla configurada para el tipo de certificado solicitado.');
        }

        $contexto = $this->resolverContexto($datos, $plantilla);

        $extra = array_filter([
            'cargo' => $tutor['cargo'] ?? null,
            'carrera' => $practica['carrera'] ?? null,
            'estudiante' => $practica['estudiante_apellido_nombres'] ?? null,
            'estudiante_dni' => $practica['estudiante_dni'] ?? null,
            'horas' => $practica['horas'] ?? null,
            'institucion' => $practica['institucion'] ?? null,
            'resolucion' => $practica['resolucion'] ?? null,
        ], fn ($valor) => $valor !== null && $valor !== '');

        $emision = $this->emisor->emitir(
            participante: $match['participante'],
            tipo: $tipo,
            plantilla: $plantilla,
            origen: $contexto,
            datos: $extra,
            alcance: 'programa',
            apiCliente: $cliente,
            externalRef: $externalRef ?: null,
            matchEstado: $match['match_estado'],
            matchDetalle: $match['match_detalle'],
        );

        if ($contexto) {
            $emision->update([
                'origen_snapshot' => array_merge($emision->origen_snapshot ?? [], array_filter([
                    'practica_carrera' => $practica['carrera'] ?? null,
                    'practica_estudiante' => $practica['estudiante_apellido_nombres'] ?? null,
                    'practica_institucion' => $practica['institucion'] ?? null,
                    'practica_periodo_inicio' => $practica['periodo_inicio'] ?? null,
                    'practica_periodo_fin' => $practica['periodo_fin'] ?? null,
                ], fn ($valor) => $valor !== null && $valor !== '')),
            ]);
        }

        return $emision->refresh();
    }

    public function generar(Emision $emision): string
    {
        return $this->emisor->generar($emision);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverTipo(array $datos): TipoReconocimiento
    {
        $slug = match ((string) ($datos['tipo'] ?? 'tutor_academico')) {
            'tutor_academico', 'tutor' => 'tutor',
            default => (string) ($datos['tipo'] ?? 'tutor'),
        };

        $tipo = TipoReconocimiento::where('slug', $slug)->first();

        if (! $tipo) {
            throw new RuntimeException("No existe el tipo de reconocimiento '{$slug}'.");
        }

        return $tipo;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function resolverPlantilla(array $datos, TipoReconocimiento $tipo): ?PlantillaCertificado
    {
        $contextoId = $datos['contexto_id'] ?? null;

        $query = PlantillaCertificado::query()
            ->where('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id)
            ->whereNotNull('contexto_id')
            ->whereNotNull('layout');

        if ($contextoId) {
            $query->where('contexto_id', $contextoId);
        }

        if (! empty($datos['plantilla_codigo'])) {
            $plantilla = (clone $query)
                ->where('nombre', $datos['plantilla_codigo'])
                ->orderByDesc('por_defecto')
                ->first();

            if ($plantilla) {
                return $plantilla;
            }
        }

        return $query->orderByDesc('por_defecto')->first();
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverContexto(array $datos, PlantillaCertificado $plantilla): ?Contexto
    {
        $contextoId = $datos['contexto_id'] ?? $plantilla->contexto_id;

        return $contextoId ? Contexto::find($contextoId) : null;
    }
}
