<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

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
                console.log("Evento recibido:", event); // <--- Agregar esto para depurar

                Swal.fire({
                    position: "bottom-end",
                    icon: "success",
                    title: event.message,
                    showConfirmButton: false,
                    timer: 2000
                });
            });
            Livewire.on('oops', (event) => {
                console.log("Error recibido:", event); // <--- Agregar esto para depurar
                Swal.fire({
                    icon: "error",
                    title: event.message,
                    timer: 3000
                    // text: "Something went wrong!",
                    // footer: '<a href="#">Why do I have this issue?</a>'
                });
            });
        });
    </script>
</body>

</html>
