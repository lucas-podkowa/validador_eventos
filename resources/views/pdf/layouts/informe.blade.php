<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('document-title', 'Informe')</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 3.4cm 2.5cm 2.8cm 2.5cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.4;
        }

        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
        }

        header img {
            width: 100%;
            height: auto;
            display: block;
        }

        footer {
            position: fixed;
            bottom: -1.95cm;
            left: 0;
            right: 0;
            font-size: 8px;
            line-height: 1.25;
            text-align: center;
            color: #000000;
        }

        .watermark {
            position: fixed;
            right: 2.7cm;
            bottom: 3.6cm;
            width: 280px;
            opacity: 0.12;
            z-index: -1000;
        }

        .watermark img {
            width: 100%;
            height: auto;
            display: block;
        }

        main {
            position: relative;
            z-index: 1;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 8px;
        }

        h1 {
            font-size: 20px;
        }

        .report-meta {
            margin-bottom: 18px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-size: 10px;
        }

        .report-meta strong {
            display: inline-block;
            min-width: 64px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 18px;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        .summary td {
            width: 25%;
        }

        .section {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <header>
        <img src="{{ public_path('logos/cabecera_informes.png') }}" alt="Cabecera institucional">
    </header>

    <footer>
        Facultad de Ingeniería - UNaM - Juan Manuel de Rosas 325 - Oberá (Mnes.) CP 3360 -
        Teléfonos/Fax: +54 03755 422169/422170 Fax: Interno 109. www.fio.unam.edu.ar.
        E-mail: extension@fio.unam.edu.ar
    </footer>

    <div class="watermark" aria-hidden="true">
        <img src="{{ public_path('logos/marca_agua.png') }}" alt="Marca de agua institucional">
    </div>

    <main>
        <h1>@yield('report-title')</h1>

        <div class="report-meta">
            <div><strong>Generado:</strong> {{ $fechaGeneracion }}</div>
            <div><strong>Filtros:</strong> {{ $filtros }}</div>
        </div>

        @yield('content')
    </main>
</body>

</html>