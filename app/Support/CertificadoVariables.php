<?php

namespace App\Support;

use App\Models\Contexto;
use App\Models\Evento;
use App\Models\Participante;

class CertificadoVariables
{
    /**
     * Construye el mapa de tokens disponibles para el cuerpo de un certificado.
     *
     * @param  array<int, array{nombre?:string, cargo?:string, imagen_path?:string}>  $firmantes
     * @return array<string, string>
     */
    public static function paraEvento(Evento $evento, Participante $participante, ?Contexto $contexto = null, array $firmantes = []): array
    {
        $contexto = $contexto ?? $evento->contexto;
        $tipoEvento = $evento->tipoEvento;

        $contextoNombre = (string) ($contexto?->nombre ?? '');
        $contextoDenominacion = (string) ($contexto?->denominacion ?? '');

        $contextoCompleto = trim($contextoNombre);
        if ($contextoDenominacion !== '') {
            $contextoCompleto = $contextoCompleto === ''
                ? $contextoDenominacion
                : "{$contextoCompleto} ({$contextoDenominacion})";
        }

        $variables = [
            'apellido' => (string) $participante->apellido,
            'nombres' => (string) $participante->nombre,
            'apellido_nombres' => NombreCertificado::paraCertificado($participante->apellido, $participante->nombre),
            'dni' => (string) $participante->dni,
            'tipo_evento' => (string) ($tipoEvento?->nombre ?? ''),
            'formula' => (string) ($tipoEvento?->formula ?? ''),
            'nombre_evento' => (string) $evento->nombre,
            'contexto' => $contextoCompleto,
            'contexto_nombre' => $contextoNombre,
            'contexto_denominacion' => $contextoDenominacion,
            'institucion' => (string) ($contexto?->institucion ?? ''),
            'resolucion' => (string) ($contexto?->resolucion ?? ''),
            'lugar' => (string) ($contexto?->lugar ?? ''),
            'fecha_rango' => FechaCertificado::rango($contexto?->fecha_inicio, $contexto?->fecha_fin),
        ];

        foreach (array_values($firmantes) as $i => $firmante) {
            $slot = $i + 1;
            $variables["firmante_{$slot}_nombre"] = (string) ($firmante['nombre'] ?? '');
            $variables["firmante_{$slot}_cargo"] = (string) ($firmante['cargo'] ?? '');
        }

        return $variables;
    }

    /**
     * Construye el mapa de tokens para un certificado emitido por un sistema externo
     * (por ejemplo, tutores académicos de PPS). El payload trae las claves `tutor` y
     * `practica`; se devuelven las mismas claves que `paraEvento` más extras de la práctica.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, array{nombre?:string, cargo?:string, imagen_path?:string}>  $firmantes
     * @return array<string, string>
     */
    public static function paraExterno(array $datos, ?Contexto $contexto = null, array $firmantes = []): array
    {
        $tutor = $datos['tutor'] ?? $datos;
        $practica = $datos['practica'] ?? [];

        $contextoNombre = (string) ($contexto?->nombre ?? '');
        $contextoDenominacion = (string) ($contexto?->denominacion ?? '');

        $contextoCompleto = trim($contextoNombre);
        if ($contextoDenominacion !== '') {
            $contextoCompleto = $contextoCompleto === ''
                ? $contextoDenominacion
                : "{$contextoNombre} ({$contextoDenominacion})";
        }

        $apellido = (string) ($tutor['apellido'] ?? '');
        $nombres = (string) ($tutor['nombres'] ?? ($tutor['nombre'] ?? ''));

        $inicio = $practica['periodo_inicio'] ?? $contexto?->fecha_inicio;
        $fin = $practica['periodo_fin'] ?? $contexto?->fecha_fin;

        $variables = [
            'apellido' => $apellido,
            'nombres' => $nombres,
            'apellido_nombres' => NombreCertificado::paraCertificado($apellido, $nombres),
            'dni' => (string) ($tutor['dni'] ?? ''),
            'tipo_evento' => (string) ($datos['tipo_evento'] ?? ''),
            'formula' => (string) ($datos['formula'] ?? ''),
            'nombre_evento' => (string) ($datos['nombre_evento'] ?? ''),
            'contexto' => $contextoCompleto,
            'contexto_nombre' => $contextoNombre,
            'contexto_denominacion' => $contextoDenominacion,
            'institucion' => (string) ($practica['institucion'] ?? ($contexto?->institucion ?? '')),
            'resolucion' => (string) ($practica['resolucion'] ?? ($contexto?->resolucion ?? '')),
            'lugar' => (string) ($contexto?->lugar ?? ''),
            'fecha_rango' => FechaCertificado::rango($inicio, $fin),
            'cargo' => (string) ($tutor['cargo'] ?? ''),
            'carrera' => (string) ($practica['carrera'] ?? ''),
            'estudiante' => (string) ($practica['estudiante_apellido_nombres'] ?? ''),
            'estudiante_dni' => (string) ($practica['estudiante_dni'] ?? ''),
            'horas' => isset($practica['horas']) ? (string) $practica['horas'] : '',
        ];

        foreach (array_values($firmantes) as $i => $firmante) {
            $slot = $i + 1;
            $variables["firmante_{$slot}_nombre"] = (string) ($firmante['nombre'] ?? '');
            $variables["firmante_{$slot}_cargo"] = (string) ($firmante['cargo'] ?? '');
        }

        return $variables;
    }

    /**
     * Tokens para una emisión cuyo origen es un contexto/agrupador (jornada, cursillo,
     * expo, PPS). `nombre_evento` apunta al nombre del contexto para reutilizar plantillas.
     *
     * @param  array<string, mixed>  $extra
     * @param  array<int, array{nombre?:string, cargo?:string, imagen_path?:string}>  $firmantes
     * @return array<string, string>
     */
    public static function paraContexto(Contexto $contexto, Participante $participante, array $extra = [], array $firmantes = []): array
    {
        $contextoNombre = (string) ($contexto->nombre ?? '');
        $contextoDenominacion = (string) ($contexto->denominacion ?? '');

        $contextoCompleto = trim($contextoNombre);
        if ($contextoDenominacion !== '') {
            $contextoCompleto = $contextoCompleto === ''
                ? $contextoDenominacion
                : "{$contextoNombre} ({$contextoDenominacion})";
        }

        $variables = array_merge([
            'apellido' => (string) $participante->apellido,
            'nombres' => (string) $participante->nombre,
            'apellido_nombres' => NombreCertificado::paraCertificado($participante->apellido, $participante->nombre),
            'dni' => (string) $participante->dni,
            'tipo_evento' => '',
            'formula' => '',
            'nombre_evento' => $contextoNombre,
            'contexto' => $contextoCompleto,
            'contexto_nombre' => $contextoNombre,
            'contexto_denominacion' => $contextoDenominacion,
            'institucion' => (string) ($contexto->institucion ?? ''),
            'resolucion' => (string) ($contexto->resolucion ?? ''),
            'lugar' => (string) ($contexto->lugar ?? ''),
            'anio' => (string) ($contexto->anio ?? ''),
            'fecha_rango' => FechaCertificado::rango($contexto->fecha_inicio, $contexto->fecha_fin),
        ], array_map(fn ($valor) => (string) ($valor ?? ''), $extra));

        return self::conFirmantes($variables, $firmantes);
    }

    /**
     * Tokens para una emisión sin origen de actividad (p. ej. un título).
     *
     * @param  array<string, mixed>  $extra
     * @param  array<int, array{nombre?:string, cargo?:string, imagen_path?:string}>  $firmantes
     * @return array<string, string>
     */
    public static function paraSinOrigen(Participante $participante, array $extra = [], array $firmantes = []): array
    {
        $variables = array_merge([
            'apellido' => (string) $participante->apellido,
            'nombres' => (string) $participante->nombre,
            'apellido_nombres' => NombreCertificado::paraCertificado($participante->apellido, $participante->nombre),
            'dni' => (string) $participante->dni,
            'tipo_evento' => '',
            'formula' => '',
            'nombre_evento' => '',
            'contexto' => '',
            'contexto_nombre' => '',
            'contexto_denominacion' => '',
            'institucion' => '',
            'resolucion' => '',
            'lugar' => '',
            'anio' => '',
            'fecha_rango' => '',
        ], array_map(fn ($valor) => (string) ($valor ?? ''), $extra));

        return self::conFirmantes($variables, $firmantes);
    }

    /**
     * @param  array<string, string>  $variables
     * @param  array<int, array{nombre?:string, cargo?:string, imagen_path?:string}>  $firmantes
     * @return array<string, string>
     */
    private static function conFirmantes(array $variables, array $firmantes): array
    {
        foreach (array_values($firmantes) as $i => $firmante) {
            $slot = $i + 1;
            $variables["firmante_{$slot}_nombre"] = (string) ($firmante['nombre'] ?? '');
            $variables["firmante_{$slot}_cargo"] = (string) ($firmante['cargo'] ?? '');
        }

        return $variables;
    }

    /**
     * Reemplaza los tokens {clave} de un texto por su valor. Por defecto escapa los
     * valores para poder renderizar el resultado como HTML (el HTML de la plantilla
     * se conserva; los datos del participante se neutralizan).
     */
    public static function reemplazar(?string $texto, array $variables, bool $escaparValores = true): string
    {
        $reemplazos = [];

        foreach ($variables as $clave => $valor) {
            $valor = (string) ($valor ?? '');
            $reemplazos['{'.$clave.'}'] = $escaparValores ? e($valor) : $valor;
        }

        return strtr((string) $texto, $reemplazos);
    }

    /**
     * Arma la lista de firmantes de un contexto, respetando el orden de los slots.
     *
     * @return array<int, array{nombre:string, cargo:string, imagen_path:?string}>
     */
    public static function firmantesDe(?Contexto $contexto): array
    {
        if (! $contexto) {
            return [];
        }

        return $contexto->firmantes
            ->map(fn ($firmante) => [
                'nombre' => (string) $firmante->nombre,
                'cargo' => $firmante->pivot?->mostrar_cargo ? (string) $firmante->cargo : '',
                'imagen_path' => $firmante->imagen_firma_path,
            ])
            ->values()
            ->all();
    }
}
