<?php

namespace App\Services;

use App\Models\ApiCliente;
use App\Models\Contexto;
use App\Models\Emision;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use App\Support\FechaCertificado;
use RuntimeException;

/**
 * Emite un certificado externo (por ejemplo, tutores académicos de PPS) delegando en
 * el emisor unificado. La emisión queda en `emision` y se valida por su código.
 *
 * El contexto es obligatorio: las plantillas viven bajo un contexto y no se permite
 * elegir una plantilla de otro contexto por error.
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

        $contexto = $this->resolverContexto($datos);
        $match = $this->resolver->resolver($tutor);
        $tipo = $this->resolverTipo($datos);
        $plantilla = $this->resolverPlantilla($datos, $tipo, $contexto);

        $extra = array_filter([
            'cargo' => $tutor['cargo'] ?? null,
            'carrera' => $practica['carrera'] ?? null,
            'estudiante' => $practica['estudiante_apellido_nombres'] ?? null,
            'estudiante_dni' => $practica['estudiante_dni'] ?? null,
            'horas' => $practica['horas'] ?? null,
            'institucion' => $practica['institucion'] ?? null,
            'resolucion' => $practica['resolucion'] ?? null,
        ], fn ($valor) => $valor !== null && $valor !== '');

        // El período de la práctica tiene prioridad sobre el rango de fechas del contexto.
        $fechaRango = FechaCertificado::rango(
            $practica['periodo_inicio'] ?? null,
            $practica['periodo_fin'] ?? null,
        );

        if ($fechaRango !== '') {
            $extra['fecha_rango'] = $fechaRango;
        }

        $alcance = $tipo->alcance_sugerido ?: 'programa';

        $emision = $this->emisor->emitir(
            participante: $match['participante'],
            tipo: $tipo,
            plantilla: $plantilla,
            origen: $contexto,
            datos: $extra,
            alcance: $alcance,
            apiCliente: $cliente,
            externalRef: $externalRef ?: null,
            matchEstado: $match['match_estado'],
            matchDetalle: $match['match_detalle'],
        );

        $emision->update([
            'origen_snapshot' => array_merge($emision->origen_snapshot ?? [], array_filter([
                'practica_carrera' => $practica['carrera'] ?? null,
                'practica_estudiante' => $practica['estudiante_apellido_nombres'] ?? null,
                'practica_institucion' => $practica['institucion'] ?? null,
                'practica_periodo_inicio' => $practica['periodo_inicio'] ?? null,
                'practica_periodo_fin' => $practica['periodo_fin'] ?? null,
            ], fn ($valor) => $valor !== null && $valor !== '')),
        ]);

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
        $slug = TipoReconocimiento::slugDesdeAlias((string) ($datos['tipo'] ?? 'tutor_academico'));

        $tipo = TipoReconocimiento::where('slug', $slug)->first();

        if (! $tipo) {
            throw new RuntimeException("No existe el tipo de reconocimiento '{$slug}'.");
        }

        return $tipo;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverContexto(array $datos): Contexto
    {
        $contextoId = $datos['contexto_id'] ?? null;

        if (! $contextoId) {
            throw new RuntimeException('El campo contexto_id es obligatorio para emitir un certificado externo.');
        }

        $contexto = Contexto::find($contextoId);

        if (! $contexto) {
            throw new RuntimeException("No existe el contexto con id '{$contextoId}'.");
        }

        return $contexto;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverPlantilla(array $datos, TipoReconocimiento $tipo, Contexto $contexto): PlantillaCertificado
    {
        $query = PlantillaCertificado::query()
            ->where('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id)
            ->where('contexto_id', $contexto->contexto_id)
            ->whereNotNull('layout');

        if (! empty($datos['plantilla_codigo'])) {
            $codigo = (string) $datos['plantilla_codigo'];
            $coincidencias = (clone $query)->where('nombre', $codigo)->get();

            if ($coincidencias->count() > 1) {
                throw new RuntimeException("Hay más de una plantilla '{$codigo}' en el contexto indicado; el nombre debe ser único.");
            }

            $plantilla = $coincidencias->first();

            if (! $plantilla) {
                throw new RuntimeException("No existe la plantilla '{$codigo}' para el tipo y contexto indicados.");
            }

            return $plantilla;
        }

        $plantilla = (clone $query)->where('por_defecto', true)->first();

        if (! $plantilla) {
            throw new RuntimeException('No hay una plantilla por defecto para el tipo y contexto indicados. Configurá una plantilla predeterminada o enviá plantilla_codigo.');
        }

        return $plantilla;
    }
}
