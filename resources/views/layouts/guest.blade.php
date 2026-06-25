<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logos/icono.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/icono.png') }}">

    <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'Laravel') }}</title>

    <meta property="og:title" content="{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'Laravel') }}">
    <meta property="og:image" content="{{ asset('logos/boton-acreditar-128.png') }}">
    <meta property="og:description" content="{{ isset($title) ? 'Formulario de inscripción para: '.$title : 'Formulario de inscripción' }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'Laravel') }}">
    <meta name="twitter:image" content="{{ asset('logos/boton-acreditar-128.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alertas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Styles -->
    @livewireStyles
    @vite('resources/css/app.css')

</head>

<body>
    {{ $slot }}
    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('alert', (event) => {
                Swal.fire({
                    position: "bottom-end",
                    icon: "success",
                    title: event.message,
                    showConfirmButton: false,
                    timer: 2000
                });
            });
            Livewire.on('oops', (event) => {
                Swal.fire({
                    icon: "error",
                    title: event.message,
                    timer: 3000
                });
            });
        });
    </script>
</body>

</html>
