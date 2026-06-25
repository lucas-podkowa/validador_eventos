<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logos/icono.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/icono.png') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Styles -->
    @livewireStyles

    <!-- Alertas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- for Bootstrap -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.css" />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
    </script>
    <!-- Incluir FontAwesome desde CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


</head>

<body class="font-sans antialiased theme-b">
    <x-banner />

    <div class="app-layout" style="font-family: 'Roboto', sans-serif;">
        <!-- Mobile sidebar overlay -->
        <div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleMobileSidebar()"></div>

        <!-- Sidebar (desktop) -->
        <aside class="app-sidebar hidden md:flex">
            @livewire('navigation-menu')
        </aside>

        <!-- Sidebar (mobile) -->
        <aside id="mobile-sidebar" class="fixed inset-y-0 left-0 z-50 w-[84vw] max-w-72 bg-brand-primary text-white transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden flex flex-col overflow-y-auto">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                <span class="text-sm font-semibold uppercase tracking-wide text-white/80">Menú</span>
                <button type="button" onclick="toggleMobileSidebar()" class="rounded-full p-2 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Cerrar menú">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            @livewire('navigation-menu')
        </aside>

        <!-- Main content -->
        <main class="app-main">
            <!-- Mobile top bar -->
            <div class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-brand-primary/10">
                <button onclick="toggleMobileSidebar()" class="text-brand-primary">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-semibold text-brand-primary">{{ config('app.name', 'Laravel') }}</span>
            </div>

            <!-- Page Heading -->
            @if (isset($header))
                <header class="content-header">
                    <div class="flex items-center gap-4">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <div class="content-area">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('modals')

    @livewireScripts

    <script>
        function openMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const isHidden = sidebar.classList.contains('-translate-x-full');

            if (isHidden) {
                openMobileSidebar();
            } else {
                closeMobileSidebar();
            }
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeMobileSidebar();
            }
        });

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
