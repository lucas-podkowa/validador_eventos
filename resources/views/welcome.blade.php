<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logos/icono.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/icono.png') }}">

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

<body class="font-sans antialiased bg-slate-50">
    <div class="relative min-h-screen flex flex-col items-center selection:bg-[#FF2D20] selection:text-white">
        <div class="relative w-full px-6 lg:px-8">

            <header class="pt-6 pb-8">
                <div class="mx-auto mb-5 flex w-full max-w-6xl justify-end">
                    @if (Route::has('login'))
                        <nav class="flex items-center gap-3">
                            @auth
                                @php
                                    $user = auth()->user();
                                @endphp

                                @if ($user)
                                    @php
                                        $panelRouteName = $user->dashboardRouteName();
                                    @endphp

                                    @if (!$panelRouteName)
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-[#163b63] shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2457d6] focus-visible:ring-offset-2">
                                                Cerrar Sesión
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            @else
                                <a href="{{ route('login') }}"
                                    class="inline-flex items-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-[#163b63] shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2457d6] focus-visible:ring-offset-2">
                                    Soy Usuario
                                </a>


                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                        class="inline-flex items-center rounded-full bg-[#2457d6] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1f4dc1] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2457d6] focus-visible:ring-offset-2">
                                        Registrarme
                                    </a>
                                @endif

                            @endauth
                        </nav>
                    @endif
                </div>

                <div class="mx-auto w-full max-w-6xl">
                    <div class="flex items-center justify-between gap-6 rounded-[2rem] bg-[#163b63] px-6 py-5 shadow-[0_18px_40px_rgba(15,23,42,0.14)] sm:px-8 lg:px-10 lg:py-6">
                        <img src="{{ asset('/logos/unam-blanco.png') }}" alt="Universidad Nacional de Misiones"
                            class="h-10 sm:h-12 lg:h-14 w-auto max-w-[220px] sm:max-w-[300px] lg:max-w-[360px] shrink-0">
                        <img src="{{ asset('/logos/logo-fi.png') }}" alt="Facultad de Ingeniería"
                            class="h-9 sm:h-10 lg:h-12 w-auto max-w-[130px] sm:max-w-[170px] lg:max-w-[210px] shrink-0">
                    </div>
                </div>
            </header>

            <!-- Contenido principal -->
            <main class="flex-1 px-6 pb-24 lg:px-8">
                <div class="mx-auto w-full max-w-6xl gap-6 lg:gap-8">
                    @if (Route::has('login'))
                        @auth
                            @php
                                $panelRouteName = auth()->user()?->dashboardRouteName();
                            @endphp

                            @if ($panelRouteName)
                                <div class="mb-5 flex justify-end">
                                    <a href="{{ route($panelRouteName) }}"
                                        class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#163b63] shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2457d6] focus-visible:ring-offset-2">
                                        Panel de Control
                                    </a>
                                </div>
                            @endif
                        @endauth

                        <h1 class="mb-4 text-center text-4xl font-bold text-gray-800">
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