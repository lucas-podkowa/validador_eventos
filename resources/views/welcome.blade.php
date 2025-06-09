<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Validador</title>

    <!-- Styles -->
    @vite('resources/css/app.css')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- for bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
    </script>

</head>

<body class="font-sans antialiased">
    <div class="relative min-h-screen flex flex-col items-center selection:bg-[#FF2D20] selection:text-white">
        <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">

            <header class="nav_superior py-10">

                <!-- Logo a la izquierda -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('/logos/logo_50.png') }}" style="width: 300px; height: auto;" alt="Logo">
                </div>
                <div>
                    @if (Route::has('login'))
                        <!-- Enlaces a la derecha -->
                        <nav class="-mx-3 flex flex-1 justify-normal nav_superior">
                            @auth
                                {{-- <a href="{{ url('/eventos') }}"
                                    class="rounded-md px-3 py-2 text-black transition hover:text-black/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#FF2D20]">
                                    Panel de Control
                                </a> --}}

                                @php
                                    $user = auth()->user();
                                @endphp

                                @if ($user && !$user->hasRole('Invitado'))
                                    @php
                                        $panelRoute = match (true) {
                                            $user->hasRole('Administrador') => route('eventos'),
                                            $user->hasRole('Revisor') => route('procesar_aprobaciones'),
                                            $user->hasRole('Asistente') => route('asistencias'),
                                            default => null,
                                        };
                                    @endphp

                                    @if ($panelRoute)
                                        <a href="{{ $panelRoute }}"
                                            class="rounded-md px-3 py-2 text-black transition hover:text-black/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#FF2D20]">
                                            Panel de Control
                                        </a>
                                    @endif
                                @endif
                            @else
                                <a class="enlace" href="{{ route('login') }}"
                                    class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                                    Soy Usuario
                                </a>


                                @if (Route::has('register'))
                                    <a class="enlace" href="{{ route('register') }}"
                                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                                        Registrarme
                                    </a>
                                @endif

                            @endauth
                        </nav>
                    @endif
                </div>
            </header>

            <!-- Contenido principal -->
            <main class="flex-1 flex items-center justify-center px-6"> <!-- Espacio suficiente para el header -->
                <div class="gap-6 lg:gap-8">
                    @if (Route::has('login'))

                        <h1 class="text-4xl font-bold text-gray-800 mb-4 align-middle text-center">
                            Eventos Activos
                        </h1>

                        <x-table>
                            <table class="w-full min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tipo de Evento
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Fecha de
                                            Inicio
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Paricipantes
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($eventosEnCurso as $evento)
                                        <tr>
                                            <td class="px-6 py-3">{{ $evento->nombre }}</td>
                                            <td class="px-6 py-3">{{ $evento->tipoEvento->nombre }}</td>
                                            <td class="px-6 py-3">{{ $evento->fecha_inicio_formatted }}</td>
                                            <td class="px-6 py-3">{{ $evento->inscriptos()->count() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </x-table>
                    @endif
                </div>
            </main>
        </div>


        <footer
            class="fixed bottom-0 left-0 w-full bg-gray-50 text-black/50 dark:bg-black dark:text-white/70 py-4 z-10">
            <div class="relative w-full max-w-7xl mx-auto px-6 text-center text-sm">
                <img src="footer_2024.png" alt="">
            </div>
        </footer>
    </div>
</body>

</html>
