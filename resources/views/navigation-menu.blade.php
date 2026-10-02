@php
    $esAdmin = auth()->user()?->hasRole('Administrador');
    $navPrefix = 'nav.'.(auth()->id() ?? 'guest').'.';
    $defaults = [
        'eventosOpen' => true,
        'ajustesOpen' => true,
        'adminOpen' => ! $esAdmin,
        'configOpen' => ! $esAdmin,
        'participantesOpen' => ! $esAdmin,
    ];
@endphp
<div class="flex flex-col h-full" x-data="(function () {
    const prefix = '{{ $navPrefix }}';
    const isReload = ((performance.getEntriesByType('navigation')[0] || {}).type) === 'reload';
    const read = (key, def) => {
        if (isReload) {
            try { sessionStorage.removeItem(prefix + key); } catch (e) {}
            return def;
        }
        let raw = null;
        try { raw = sessionStorage.getItem(prefix + key); } catch (e) {}
        return raw === null ? def : JSON.parse(raw);
    };
    return {
        eventosOpen: read('eventosOpen', @js($defaults['eventosOpen'])),
        ajustesOpen: read('ajustesOpen', @js($defaults['ajustesOpen'])),
        adminOpen: read('adminOpen', @js($defaults['adminOpen'])),
        configOpen: read('configOpen', @js($defaults['configOpen'])),
        participantesOpen: read('participantesOpen', @js($defaults['participantesOpen'])),
        toggle(section) {
            this[section] = ! this[section];
            try { sessionStorage.setItem(prefix + section, JSON.stringify(this[section])); } catch (e) {}
        }
    };
})()">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="{{ route('welcome') }}" class="flex items-center gap-1">
            <img src="{{ asset('logos/logo_acreditar.png') }}" alt="Acreditar"
                class="h-12 w-auto max-w-[120px] md:h-16 md:max-w-[160px]" style="filter: brightness(0) invert(1);">
        </a>
    </div>

    <!-- Navigation Items -->
    <nav class="sidebar-nav flex-1 py-4">
        {{-- EVENTOS --}}
        @role('Administrador|Gestor|Colaborador|Revisor')
        <button @click="toggle('eventosOpen')"
            class="sidebar-section-label mt-2 w-full text-left flex items-center justify-between">
            <span>Eventos</span>
            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': eventosOpen }"></i>
        </button>

        <div x-show="eventosOpen" class="collapse-content">
            @role('Administrador|Revisor|Gestor')
            <a href="{{ route('procesar_aprobaciones') }}"
                class="{{ request()->routeIs('procesar_aprobaciones') ? 'active' : '' }}">
                <i class="fa-solid fa-check-double w-5 text-center"></i>
                <span>Aprobaciones</span>
            </a>
            @endrole

            @role('Administrador|Colaborador|Gestor')
            <a href="{{ route('asistencias') }}" class="{{ request()->routeIs('asistencias') ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-check w-5 text-center"></i>
                <span>Asistencias</span>
            </a>
            @endrole

            @role('Administrador|Gestor')
            <a href="{{ route('eventos') }}" class="{{ request()->routeIs('eventos') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-days w-5 text-center"></i>
                <span>Eventos</span>
            </a>
            @endrole

            @role('Administrador')
            <a href="{{ route('registrar_evento') }}"
                class="{{ request()->routeIs('registrar_evento') ? 'active' : '' }}">
                <i class="fa-solid fa-plus w-5 text-center"></i>
                <span>Registrar Evento</span>
            </a>

            <div class="mt-1">
                <button @click="toggle('ajustesOpen')"
                    class="sidebar-section-label sidebar-subsection w-full text-left flex items-center justify-between">
                    <span>Ajustes</span>
                    <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': ajustesOpen }"></i>
                </button>

                <div x-show="ajustesOpen" class="collapse-content">
                    <a href="{{ route('admin.tipos_evento') }}"
                        class="{{ request()->routeIs('admin.tipos_evento') ? 'active' : '' }}">
                        <i class="fa-solid fa-list w-5 text-center"></i>
                        <span>Tipos de Evento</span>
                    </a>

                    <a href="{{ route('admin.destinatarios') }}"
                        class="{{ request()->routeIs('admin.destinatarios') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-tag w-5 text-center"></i>
                        <span>Destinatarios</span>
                    </a>

                    <a href="{{ route('indicadores') }}" class="{{ request()->routeIs('indicadores') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line w-5 text-center"></i>
                        <span>Indicadores</span>
                    </a>
                </div>
            </div>
            @endrole
        </div>
        @endrole

        {{-- ADMINISTRACIÓN --}}
        @role('Administrador|Gestor')
        <button @click="toggle('adminOpen')"
            class="sidebar-section-label mt-2 w-full text-left flex items-center justify-between">
            <span>Administración</span>
            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': adminOpen }"></i>
        </button>

        <div x-show="adminOpen" class="collapse-content">
            @role('Administrador')
            <a href="{{ route('emisor_certificados') }}"
                class="{{ request()->routeIs('emisor_certificados') ? 'active' : '' }}">
                <i class="fa-solid fa-certificate w-5 text-center"></i>
                <span>Emisión</span>
            </a>

            <a href="{{ route('emision_masiva') }}"
                class="{{ request()->routeIs('emision_masiva') ? 'active' : '' }}">
                <i class="fa-solid fa-layer-group w-5 text-center"></i>
                <span>Emisión masiva</span>
            </a>

            <a href="{{ route('admin.certificados') }}"
                class="{{ request()->routeIs('admin.certificados') ? 'active' : '' }}">
                <i class="fa-solid fa-file-circle-check w-5 text-center"></i>
                <span>Certificados emitidos</span>
            </a>

            <a href="{{ route('informes') }}" class="{{ request()->routeIs('informes') ? 'active' : '' }}">
                <i class="fa-solid fa-file-lines w-5 text-center"></i>
                <span>Informes</span>
            </a>
            @endrole

            @role('Administrador|Gestor')
            <a href="{{ route('participantes') }}" class="{{ request()->routeIs('participantes') ? 'active' : '' }}">
                <i class="fa-solid fa-users w-5 text-center"></i>
                <span>Participantes</span>
            </a>
            @endrole

            @role('Administrador')
            <a href="{{ route('admin.solicitudes_dni') }}"
                class="{{ request()->routeIs('admin.solicitudes_dni') ? 'active' : '' }}">
                <i class="fa-solid fa-id-card w-5 text-center"></i>
                <span>Solicitudes DNI</span>
            </a>

            <a href="{{ route('usuarios') }}" class="{{ request()->routeIs('usuarios') ? 'active' : '' }}">
                <i class="fa-solid fa-user-shield w-5 text-center"></i>
                <span>Usuarios</span>
            </a>
            @endrole
        </div>
        @endrole

        {{-- CONFIGURACIÓN --}}
        @role('Administrador')
        <button @click="toggle('configOpen')"
            class="sidebar-section-label mt-2 w-full text-left flex items-center justify-between">
            <span>Configuración</span>
            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': configOpen }"></i>
        </button>

        <div x-show="configOpen" class="collapse-content">
            <a href="{{ route('admin.categorias') }}" class="{{ request()->routeIs('admin.categorias') ? 'active' : '' }}">
                <i class="fa-solid fa-folder-open w-5 text-center"></i>
                <span>Categorías</span>
            </a>

            <a href="{{ route('admin.contextos') }}" class="{{ request()->routeIs('admin.contextos') ? 'active' : '' }}">
                <i class="fa-solid fa-layer-group w-5 text-center"></i>
                <span>Contextos</span>
            </a>

            <a href="{{ route('admin.firmantes') }}" class="{{ request()->routeIs('admin.firmantes') ? 'active' : '' }}">
                <i class="fa-solid fa-signature w-5 text-center"></i>
                <span>Firmantes</span>
            </a>

            <a href="{{ route('admin.tipos_reconocimiento') }}"
                class="{{ request()->routeIs('admin.tipos_reconocimiento') ? 'active' : '' }}">
                <i class="fa-solid fa-award w-5 text-center"></i>
                <span>Tipos de Reconocimiento</span>
            </a>

            <a href="{{ route('admin.api_clientes') }}"
                class="{{ request()->routeIs('admin.api_clientes') ? 'active' : '' }}">
                <i class="fa-solid fa-key w-5 text-center"></i>
                <span>Clientes de API</span>
            </a>

            <a href="{{ route('admin.certificados_externos') }}"
                class="{{ request()->routeIs('admin.certificados_externos') ? 'active' : '' }}">
                <i class="fa-solid fa-file-shield w-5 text-center"></i>
                <span>Certificados externos</span>
            </a>
        </div>
        @endrole

        {{-- PARTICIPANTES --}}
        <button @click="toggle('participantesOpen')"
            class="sidebar-section-label mt-2 w-full text-left flex items-center justify-between">
            <span>Participantes</span>
            <i class="fa-solid fa-chevron-down text-xs transition-transform"
                :class="{ 'rotate-180': participantesOpen }"></i>
        </button>

        <div x-show="participantesOpen" class="collapse-content">
            <a href="{{ route('mis_certificados') }}" class="{{ request()->routeIs('mis_certificados') ? 'active' : '' }}">
                <i class="fa-solid fa-certificate w-5 text-center"></i>
                <span>Mis Certificados</span>
            </a>

            <a href="{{ route('mis_datos') }}" class="{{ request()->routeIs('mis_datos') ? 'active' : '' }}">
                <i class="fa-solid fa-user-pen w-5 text-center"></i>
                <span>Mis Datos</span>
            </a>
        </div>
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
            <a href="{{ route('profile.show') }}"
                class="flex items-center gap-2 text-sm text-white/70 hover:text-white py-1 px-2 rounded hover:bg-white/10 transition">
                <i class="fa-solid fa-user w-4 text-center text-xs"></i>
                <span>Perfil</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="w-full"
                onsubmit="try { Object.keys(sessionStorage).filter(function (k) { return k.indexOf('nav.') === 0; }).forEach(function (k) { sessionStorage.removeItem(k); }); } catch (e) {}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2 text-sm text-red-300 hover:text-red-200 py-1 px-2 rounded hover:bg-white/10 transition text-left">
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
