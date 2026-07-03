<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asistencias - {{ $evento->nombre }}</title>
    <style>
        @page {
            margin: 95px 25px;
        }

        body {
            font-family: sans-serif;
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
            bottom: -30px;
            left: 0px;
            right: 0px;
            height: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
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
    <h2>Asistencias - {{ $evento->nombre }}</h2>
    <table>
        <thead>
            <tr>
                <th>Participante</th>
                <th>DNI</th>
                @foreach ($sesiones as $sesion)
                    <th>{{ $sesion->nombre }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($datos as $fila)
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>{{ $fila['dni'] }}</td>
                    @foreach ($fila['asistencias'] as $estado)
                        <td>{{ $estado ? 'SI' : 'NO' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <footer>
        <div style="text-align: center; font-size: 10px; color: #555;"></div>
    </footer>
    <script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script("
            \$font = \$fontMetrics->get_font('helvetica', 'normal');
            \$size = 9;
            \$y = \$pdf->get_height() - 35;

            \$datetime_text = date('d/m/Y H:i');
            \$x_left = 15;
            \$pdf->text(\$x_left, \$y, \$datetime_text, \$font, \$size);

            \$page = \$PAGE_NUM;
            \$total = \$PAGE_COUNT;
            \$page_text = 'Página ' . \$page . ' de ' . \$total;

            \$text_width = \$fontMetrics->get_text_width(\$page_text, \$font, \$size);
            \$x_right = \$pdf->get_width() - 15 - \$text_width;
            \$pdf->text(\$x_right, \$y, \$page_text, \$font, \$size);
        ");
    }
</script>
</body>

</html>
