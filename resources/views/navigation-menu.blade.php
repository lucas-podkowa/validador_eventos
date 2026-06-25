<div class="flex flex-col h-full" x-data="{ adminOpen: true }">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="{{ route('welcome') }}" class="flex items-center gap-1">
            <img src="{{ asset('logos/logo_acreditar.png') }}" alt="Acreditar"
                class="h-12 w-auto max-w-[120px] md:h-16 md:max-w-[160px]" style="filter: brightness(0) invert(1);">
        </a>
    </div>

    <!-- Navigation Items -->
    <nav class="sidebar-nav flex-1 py-4">
        @role('Administrador|Gestor')
        <a href="{{ route('eventos') }}" class="{{ request()->routeIs('eventos') ? 'active' : '' }}">
            <i class="fa-solid fa-calendar-days w-5 text-center"></i>
            <span>Eventos</span>
        </a>
        @endrole

        @role('Administrador')
        <a href="{{ route('registrar_evento') }}" class="{{ request()->routeIs('registrar_evento') ? 'active' : '' }}">
            <i class="fa-solid fa-plus w-5 text-center"></i>
            <span>Registrar Evento</span>
        </a>
        @endrole

        @role('Administrador|Colaborador|Gestor')
        <a href="{{ route('asistencias') }}" class="{{ request()->routeIs('asistencias') ? 'active' : '' }}">
            <i class="fa-solid fa-clipboard-check w-5 text-center"></i>
            <span>Asistencias</span>
        </a>
        @endrole

        @role('Administrador|Revisor|Gestor')
        <a href="{{ route('procesar_aprobaciones') }}" class="{{ request()->routeIs('procesar_aprobaciones') ? 'active' : '' }}">
            <i class="fa-solid fa-check-double w-5 text-center"></i>
            <span>Aprobaciones</span>
        </a>
        @endrole

        @role('Administrador|Gestor')
        <a href="{{ route('participantes') }}" class="{{ request()->routeIs('participantes') ? 'active' : '' }}">
            <i class="fa-solid fa-users w-5 text-center"></i>
            <span>Participantes</span>
        </a>
        @endrole

        @role('Administrador')
        <button @click="adminOpen = !adminOpen" class="sidebar-section-label mt-2 w-full text-left flex items-center justify-between">
            <span>Administración</span>
            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': adminOpen }"></i>
        </button>

        <div x-show="adminOpen" class="collapse-content">
        <a href="{{ route('admin.categorias') }}" class="{{ request()->routeIs('admin.categorias') ? 'active' : '' }}">
            <i class="fa-solid fa-folder-open w-5 text-center"></i>
            <span>Categorías</span>
        </a>

        <a href="{{ route('admin.destinatarios') }}" class="{{ request()->routeIs('admin.destinatarios') ? 'active' : '' }}">
            <i class="fa-solid fa-user-tag w-5 text-center"></i>
            <span>Destinatarios</span>
        </a>

        <a href="{{ route('emisor_certificados') }}" class="{{ request()->routeIs('emisor_certificados') ? 'active' : '' }}">
            <i class="fa-solid fa-certificate w-5 text-center"></i>
            <span>Emisión</span>
        </a>

        <a href="{{ route('indicadores') }}" class="{{ request()->routeIs('indicadores') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-line w-5 text-center"></i>
            <span>Indicadores</span>
        </a>

        <a href="{{ route('informes') }}" class="{{ request()->routeIs('informes') ? 'active' : '' }}">
            <i class="fa-solid fa-file-lines w-5 text-center"></i>
            <span>Informes</span>
        </a>

        <a href="{{ route('admin.tipos_evento') }}" class="{{ request()->routeIs('admin.tipos_evento') ? 'active' : '' }}">
            <i class="fa-solid fa-list w-5 text-center"></i>
            <span>Tipos de Evento</span>
        </a>

        <a href="{{ route('usuarios') }}" class="{{ request()->routeIs('usuarios') ? 'active' : '' }}">
            <i class="fa-solid fa-user-shield w-5 text-center"></i>
            <span>Usuarios</span>
        </a>
        </div>
        @endrole
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="flex items-center gap-3 mb-3">
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <img class="h-8 w-8 rounded-full object-cover border border-white/30"
                    src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
            @else
                <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-white/50 truncate">{{ Auth::user()->email }}</div>
            </div>
        </div>

        <div class="flex flex-col gap-1">
            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 text-sm text-white/70 hover:text-white py-1 px-2 rounded hover:bg-white/10 transition">
                <i class="fa-solid fa-user w-4 text-center text-xs"></i>
                <span>Perfil</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 text-sm text-red-300 hover:text-red-200 py-1 px-2 rounded hover:bg-white/10 transition text-left">
                    <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>

        <div class="mt-3 pt-3 border-t border-white/10 text-xs text-white/40">
            <div class="font-semibold">Sistemas</div>
            <div>{{ config('app.name', 'Laravel') }}</div>
        </div>
    </div>
</div>
