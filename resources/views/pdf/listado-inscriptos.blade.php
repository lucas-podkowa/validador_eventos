<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de inscriptos - {{ $evento->nombre }}</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700&family=Roboto:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        @page {
            margin: 95px 25px;
        }

        body {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
        }

        header {
            position: fixed;
            top: -80px;
            left: 0px;
            right: 0px;
            height: 75px;
            border-bottom: 1px solid #003366;
        }

        .header-row {
            width: 100%;
        }

        .header-row table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-row td {
            border: none;
            padding: 0;
        }

        .header-logo-left img,
        .header-logo-right img {
            height: 52px;
            width: auto;
        }

        .header-logo-left {
            text-align: left;
        }

        .header-logo-right {
            text-align: right;
        }

        footer {
            position: fixed;
            bottom: -10px;
            left: 0px;
            right: 0px;
            height: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .tabla-inscriptos th,
        .tabla-inscriptos td {
            font-family: 'Roboto Condensed', sans-serif;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-row">
            <table>
                <tr>
                    <td class="header-logo-left">
                        <img src="{{ public_path('logos/logo_fi_azul.png') }}" alt="Facultad de Ingeniería azul">
                    </td>
                    <td class="header-logo-right">
                        <img src="{{ public_path('logos/logo_acreditar_azul.png') }}" alt="Acreditar azul">
                    </td>
                </tr>
            </table>
        </div>
    </header>
    <footer>
        <div style="text-align: center; font-size: 10px; color: #555;">
            <!-- Este bloque queda visualmente en el footer -->
        </div>

        <!-- Bloque PHP fuera del flujo visual -->
    </footer>
    <script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script("
            \$font = \$fontMetrics->get_font('helvetica', 'normal');
            \$size = 9;
            \$y = \$pdf->get_height() - 35;

            // Fecha y hora
            \$datetime_text = date('d/m/Y H:i');
            \$x_left = 15;
            \$pdf->text(\$x_left, \$y, \$datetime_text, \$font, \$size);

            // Página actual y total
            \$page = \$PAGE_NUM;
            \$total = \$PAGE_COUNT;
            \$page_text = 'Página ' . \$page . ' de ' . \$total;

            \$text_width = \$fontMetrics->get_text_width(\$page_text, \$font, \$size);
            \$x_right = \$pdf->get_width() - 15 - \$text_width;
            \$pdf->text(\$x_right, \$y, \$page_text, \$font, \$size);
        ");
    }
</script>


    <main>
        @if (!empty($mostrarDetalles))
            <h2>Detalles del Evento</h2>
            <div style="border:1px solid #ddd; padding:8px; margin-bottom:15px;">
                <p style="margin:0 0 4px;"><strong>Nombre:</strong> {{ $evento->nombre }}</p>
                <p style="margin:0 0 4px;"><strong>Fecha de Inicio:</strong> {{ $evento->fecha_inicio_formatted }}</p>
                <p style="margin:0 0 4px;"><strong>Tipo de Evento:</strong> {{ $evento->tipoEvento->nombre ?? 'N/A' }}</p>
                <p style="margin:0 0 4px;"><strong>Categoría:</strong> {{ $evento->categoria->nombre ?? '—' }}</p>
                <p style="margin:0 0 4px;"><strong>Lugar:</strong> {{ $evento->lugar }}</p>
                <p style="margin:0 0 4px;"><strong>Certificación:</strong>
                    {{ $evento->por_aprobacion ? 'Por Aprobación' : 'Por Asistencia' }}</p>
                <p style="margin:0;"><strong>Responsable:</strong>
                    {{ $evento->responsable ? $evento->responsable->nombre.' '.$evento->responsable->apellido : 'Sin asignar' }}
                </p>
            </div>

            @if (!empty($disertantesYColaboradores) && $disertantesYColaboradores->isNotEmpty())
                <h3>Disertantes y Colaboradores</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>DNI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($disertantesYColaboradores as $persona)
                            <tr>
                                <td>{{ $persona->rol->nombre ?? '—' }}</td>
                                <td>{{ $persona->participante->nombre ?? '' }}</td>
                                <td>{{ $persona->participante->apellido ?? '' }}</td>
                                <td>{{ $persona->participante->dni ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        <h2>{{ !empty($mostrarDetalles) ? 'Listado de Inscriptos' : 'Listado de inscriptos - '.$evento->nombre }}</h2>

        <table class="tabla-inscriptos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>DNI</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inscriptos as $inscripto)
                    <tr>
                        <td>{{ $inscripto->participante->nombre }}</td>
                        <td>{{ $inscripto->participante->apellido }}</td>
                        <td>{{ $inscripto->participante->dni }}</td>
                        <td>{{ $inscripto->participante->mail }}</td>
                        <td>{{ $inscripto->participante->telefono }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
</body>

</html>
