<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de inscriptos - {{ $evento->nombre }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page {
            margin: 100px 25px;
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
            height: 50px;
        }

        footer {
            position: fixed;
            bottom: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
        }

        .header-logo {
            text-align: right;
        }

        .header-logo img {
            width: 180px;
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

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-logo">
            <img src="{{ public_path('logos/logo-unam-color.png') }}">
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
        <h2>Listado de inscriptos - {{ $evento->nombre }}</h2>

        <table>
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
