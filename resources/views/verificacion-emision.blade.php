<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de certificado</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Roboto, 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f4f4f4;
            color: #0A1B3A;
        }

        .fondo {
            min-height: 100vh;
            background-image: url('{{ $base64 }}');
            background-position: center center;
            background-size: contain;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .tarjeta {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #d7dce5;
            border-radius: 12px;
            max-width: 560px;
            width: 100%;
            padding: 28px 32px;
            box-shadow: 0 8px 24px rgba(10, 27, 58, 0.12);
        }

        .tarjeta h1 {
            margin: 0 0 4px;
            font-size: 1.5rem;
        }

        .estado {
            display: inline-block;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }

        .estado.valido {
            background: #e3f5e9;
            color: #17663a;
        }

        .estado.anulado {
            background: #fff4e0;
            color: #8a5a1f;
        }

        .estado.invalido {
            background: #fbe4e4;
            color: #8a1f1f;
        }

        dl {
            margin: 0;
        }

        dt {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #5b6b85;
            margin-top: 14px;
        }

        dd {
            margin: 2px 0 0;
            font-size: 1.05rem;
        }
    </style>
</head>

@php
    $persona = $emision?->participante;
    $snapshot = $emision?->origen_snapshot ?? [];
    $tipo = $emision?->tipoReconocimiento?->nombre;

    $origenPrincipal = $snapshot['nombre'] ?? null;
    $detalleOrigen = array_filter([
        $snapshot['contexto_nombre'] ?? null,
        $snapshot['denominacion'] ?? null,
        $snapshot['tipo_evento'] ?? null,
    ]);
@endphp

<body>
    <div class="fondo">
        <div class="tarjeta">
            @if ($valido)
                <span class="estado valido">Certificado válido</span>
                <h1>{{ $tipo ? 'Certificado de '.$tipo : 'Certificado' }}</h1>

                <dl>
                    <dt>Otorgado a</dt>
                    <dd>{{ trim(($persona?->nombre ?? '').' '.($persona?->apellido ?? '')) }}</dd>

                    <dt>DNI</dt>
                    <dd>{{ $persona?->dni ?? '' }}</dd>

                    @if ($origenPrincipal)
                        <dt>Actividad</dt>
                        <dd>{{ $origenPrincipal }}</dd>
                    @endif

                    @if (! empty($detalleOrigen))
                        <dt>Detalle</dt>
                        <dd>{{ implode(' · ', $detalleOrigen) }}</dd>
                    @endif

                    @if (! empty($snapshot['institucion']))
                        <dt>Institución</dt>
                        <dd>{{ $snapshot['institucion'] }}</dd>
                    @endif

                    @if (! empty($snapshot['resolucion']))
                        <dt>Resolución</dt>
                        <dd>{{ $snapshot['resolucion'] }}</dd>
                    @endif

                    @if ($emision?->emitida_en)
                        <dt>Fecha de emisión</dt>
                        <dd>{{ $emision->emitida_en->format('d/m/Y') }}</dd>
                    @endif
                </dl>
            @elseif ($anulado)
                <span class="estado anulado">Certificado anulado</span>
                <h1>Este certificado fue anulado</h1>
                <p>El documento existió pero su emisión fue dejada sin efecto por la organización. Si creés que es un
                    error, comunicate con la organización.</p>
            @else
                <span class="estado invalido">Certificado no válido</span>
                <h1>No encontramos este certificado</h1>
                <p>El código escaneado no corresponde a un certificado vigente o fue anulado. Si creés que es un
                    error, comunicate con la organización.</p>
            @endif
        </div>
    </div>
</body>

</html>
