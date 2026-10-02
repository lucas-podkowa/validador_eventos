<div>
    <div x-data="{ filtrosAbiertos: false }" class="mb-3">
        <div class="flex items-center gap-2">
            <button type="button" @click="filtrosAbiertos = !filtrosAbiertos"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition">
                <i class="fa-solid fa-filter text-gray-500"></i>
                Filtros
                <i class="fas text-xs text-gray-500"
                    :class="filtrosAbiertos ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
            @if ($this->filtrosActivos > 0)
                <span
                    class="inline-flex items-center px-2 py-0.5 text-xs font-semibold text-blue-700 bg-blue-100 border border-blue-200 rounded-full">
                    {{ $this->filtrosActivos }} activo{{ $this->filtrosActivos === 1 ? '' : 's' }}
                </span>
                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex items-center px-2 py-0.5 text-xs text-gray-600 hover:text-red-600 transition"
                    title="Limpiar todos los filtros">
                    <i class="fa-solid fa-xmark mr-1"></i> Limpiar
                </button>
            @endIf
        </div>

        <div x-show="filtrosAbiertos" x-cloak class="mt-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nombre del Evento"
                    class="w-full p-2 border border-gray-300 rounded" />

                <input type="text" wire:model.live.debounce.300ms="searchResponsable" placeholder="Responsable del Evento"
                    class="w-full p-2 border border-gray-300 rounded" />

                <input type="text" wire:model.live.debounce.300ms="searchParticipante" placeholder="Buscar Participante por DNI"
                    class="w-full p-2 border border-gray-300 rounded" />

                <select wire:model.live="searchTipoEvento" class="w-full p-2 border border-gray-300 rounded">
                    <option value="">Todos los Tipos de Evento</option>
                    @foreach ($tiposEvento as $tipoEvento)
                        <option value="{{ $tipoEvento->tipo_evento_id }}">{{ $tipoEvento->nombre }}</option>
                    @endforeach
                </select>

                <select wire:model.live="searchCategoria" class="w-full p-2 border border-gray-300 rounded">
                    <option value="">Todas las Categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->categoria_id }}">{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <x-table>
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th wire:click="order('nombre')"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer">
                        Nombre
                        @if ($sort === 'nombre')
                            <i
                                class="fas {{ $direction === 'asc' ? 'fa-sort-alpha-up-alt' : 'fa-sort-alpha-down-alt' }} float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort float-right mt-1"></i>
                        @endif
                    </th>
                    <th wire:click="order('tipo_evento')"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer">
                        Tipo de Evento
                        @if ($sort === 'tipo_evento')
                            <i
                                class="fas {{ $direction === 'asc' ? 'fa-sort-alpha-up-alt' : 'fa-sort-alpha-down-alt' }} float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort float-right mt-1"></i>
                        @endif
                    </th>
                    <th wire:click="order('fecha_inicio')"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer">
                        Fecha de Inicio
                        @if ($sort === 'fecha_inicio')
                            <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort float-right mt-1"></i>
                        @endif
                    </th>
                    <th wire:click="order('categoria')"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer">
                        Categoría
                        @if ($sort === 'categoria')
                            <i class="fas {{ $direction === 'asc' ? 'fa-sort-alpha-up-alt' : 'fa-sort-alpha-down-alt' }} float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort float-right mt-1"></i>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventosFinalizados as $evento)
                    <tr>
                        <td class="px-6 py-3">{{ $evento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->fecha_inicio_formatted }}</td>
                        <td class="px-6 py-3">{{ $evento->categoria->nombre ?? '—' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium relative overflow-visible">

                            <div x-data="{ open: false }">
                                <button @click="open = !open"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md focus:outline-none flex items-center">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>

                                <div x-show="open" @click.away="open = false"
                                    class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg"
                                    style="z-index: 9999;">

                                    @if ($evento->por_aprobacion && !$evento->revisado)
                                        {{-- Aviso informativo: no bloquea la gestión del evento --}}
                                        <div class="flex items-center justify-center text-yellow-600 py-2 px-4 border-b border-gray-100">
                                            <i class="fa-solid fa-triangle-exclamation fa-xl mr-2"
                                                title="Requiere revisar Aprobaciones"></i>
                                            Requiere aprobación
                                        </div>
                                    @endif

                                    <a wire:click="detail({{ $evento }})"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="mr-2 fa-solid fa-qrcode fa-xl fa-fw"></i>
                                        Ver Códigos QR
                                    </a>

                                    <a wire:click="descargarInscriptos({{ $evento }})"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="mr-2 fa-solid fa-users fa-xl fa-fw text-indigo-500"></i>
                                        Lista de Inscriptos
                                    </a>

                                    @role('Administrador')
                                    @if (!$evento->por_aprobacion || $evento->revisado)
                                        <a wire:click="emitir({{ $evento }})"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="mr-2 fa-solid fa-file-pdf fa-xl fa-fw text-blue-500"></i>
                                            {{ $modo === 'finalizados' ? 'Reemitir Certificados' : 'Emitir Certificados' }}
                                        </a>
                                    @endif
                                    @endrole

                                    @if ($modo === 'finalizados' && $evento->certificados_disponibles)
                                        <a wire:click="abrirModalMail({{ $evento }})"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="mr-1 fa-solid fa-envelope fa-xl text-purple-600"></i>
                                            Enviar por Mail
                                        </a>
                                        <a wire:click="abrirCarpeta('{{ $evento->certificado_path }}')"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="mr-1 fa-solid fa-folder-open fa-xl"></i>
                                            Descargar Certificados
                                        </a>
                                    @endif

                                    @if ($modo === 'a_certificar')
                                        <hr class="border-gray-200">

                                        <a onclick="confirmDevolverEvento('{{ addslashes($evento->evento_id) }}')"
                                            class="block px-4 py-1 text-orange-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="fa-solid fa-rotate-left fa-xl"></i>
                                            Devolver a Eventos en Curso
                                        </a>

                                        @if ($evento->por_aprobacion)
                                            <a wire:click="modalRevisor('{{ addslashes($evento->evento_id) }}')"
                                                class="block px-4 py-1 text-blue-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                                <i class="fas fa-user-check fa-xl"></i>
                                                Editar Revisor
                                            </a>
                                        @endif

                                        @role('Administrador')
                                        @if ($evento->por_aprobacion)
                                            <a onclick="confirmAprobacionInstantanea('{{ addslashes($evento->evento_id) }}')"
                                                class="block px-4 py-1 text-green-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                                <i class="fa-solid fa-circle-check fa-xl"></i>
                                                Aprobación Instantánea
                                            </a>
                                            <a onclick="confirmQuitarAprobacion('{{ addslashes($evento->evento_id) }}')"
                                                class="block px-4 py-1 text-red-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                                <i class="fa-solid fa-ban fa-xl"></i>
                                                Quitar Aprobación
                                            </a>
                                        @endif
                                        @endrole
                                    @endif
                                </div>
                            </div>



                        </td>
                    </tr>
                @endforeach

            </tbody>
        </table>
    </x-table>
    <!-- Paginación -->
    <div class="mt-4">
        {{ $eventosFinalizados->links() }}
    </div>

    {{-- ------------------------ DIALOG MODAL ver QR--------------------------- --}}

    <x-dialog-modal wire:model="open_detail">
        <x-slot name="title">
            Participantes del Evento
        </x-slot>

        <x-slot name="content">
            <ul>
                @foreach ($participantes as $p)
                    <li class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $p->nombre }} {{ $p->apellido }}
                        </div>
                        {{-- <div class="w-[100px] h-[100px]">
                            {!! $p->pivot->qrcode !!}
                        </div> --}}
                        <div>
                            <img src="{{ $p['qrcode_base64'] }}" width="100" height="100" alt="QR Code" />
                        </div>
                    </li>
                @endforeach
            </ul>

        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('open_detail', false)">
                Volver
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    {{-- ------------------------ DIALOG MODAL editar revisor--------------------------- --}}

    <x-dialog-modal wire:model="open_modal_revisor">
        <x-slot name="title">
            Editar Revisor del Evento
        </x-slot>

        <x-slot name="content">
            @if ($evento_selected)
                <p class="mb-3 text-sm text-gray-600">
                    Revisor actual:
                    <span class="font-semibold text-gray-800">
                        {{ $evento_selected->revisor->name ?? 'No asignado' }}
                    </span>
                </p>
            @endif

            <div>
                <input type="text" wire:model.live="busqueda_usuario" class="w-full rounded border-gray-300"
                    placeholder="Buscar por nombre o email...">
            </div>

            <div class="mt-4 max-h-64 overflow-y-auto">
                @forelse ($usuarios_filtrados as $usuario)
                    <button type="button" wire:key="revisor-{{ $usuario->id }}"
                        wire:click="seleccionarRevisor({{ $usuario->id }})"
                        class="w-full flex items-center gap-2 mb-2 p-2 text-left rounded border transition {{ (int) $usuario_seleccionado_id === (int) $usuario->id ? 'bg-blue-50 border-blue-400' : 'border-gray-200 hover:bg-gray-100' }}">
                        <i
                            class="fa-solid {{ (int) $usuario_seleccionado_id === (int) $usuario->id ? 'fa-circle-check text-green-600' : 'fa-circle text-gray-300' }}"></i>
                        <span>{{ $usuario->name }} - {{ $usuario->email }}</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">Sin resultados. Busque un usuario con rol Revisor.</p>
                @endforelse
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex">
                <x-secondary-button wire:click="$set('open_modal_revisor', false)">
                    Volver
                </x-secondary-button>
                <button type="button" wire:click="guardarRevisor" style="font-size: 0.75rem; font-weight: 600"
                    class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                    Guardar
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>

    {{-- ------------------------ DIALOG MODAL subir plantilla certificado--------------------------- --}}

    <x-dialog-modal wire:model="open_emitir">
        <x-slot name="title">
            <h4 class="text-md font-semibold mb-2 mt-4 text-blue-600">Plantillas del contexto para los certificados</h4>
        </x-slot>

        <x-slot name="content">
            {{-- Información del Evento --}}
            @if ($evento_selected)
                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-md space-y-1">
                    <div class="flex items-center text-sm text-gray-700">
                        <i class="fa-solid fa-layer-group text-indigo-600 mr-2 w-5 text-center"></i>
                        <span class="font-medium mr-1">Contexto:</span>
                        {{ $evento_selected->contexto->nombre ?? 'Sin asignar' }}
                    </div>
                    {{-- Responsable del Evento --}}
                    @if ($evento_selected->responsable)
                        <div class="flex items-center text-sm text-gray-700">
                            <i class="fa-solid fa-user-tie text-indigo-600 mr-2 w-5 text-center"></i>
                            <span class="font-medium mr-1">Responsable:</span>
                            {{ $evento_selected->responsable->nombre }} {{ $evento_selected->responsable->apellido }}
                        </div>
                    @endif

                    {{-- Descargar Disposición Respaldatoria --}}
                    @if ($evento_selected->planillaInscripcion?->disposicion)
                        <div class="flex items-center text-sm">
                            <i class="fa-solid fa-file-pdf text-red-500 mr-2 w-5 text-center"></i>
                            <a wire:click="descargarDisposicion"
                                class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer font-medium">
                                Descargar Disposición Respaldatoria
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Asignación de contexto (sin salir de la instancia de certificación) --}}
            @if ($evento_selected && is_null($evento_selected->certificado_path))
                <details class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50/40 p-4"
                    @if (! $evento_selected->contexto_id) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-indigo-800">
                        {{ $evento_selected->contexto_id ? 'Cambiar contexto del evento' : 'Asignar contexto del evento' }}
                    </summary>
                    <p class="mt-2 text-xs text-gray-500">
                        Asignar categoría y contexto no modifica el QR ni las aprobaciones de los participantes.
                    </p>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Categoría</label>
                            <select wire:model.live="categoria_asignada_id"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Seleccionar --</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->categoria_id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            @error('categoria_asignada_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contexto</label>
                            <select wire:model="contexto_asignado_id"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @disabled(empty($contextos_asignables))>
                                <option value="">-- Seleccionar --</option>
                                @foreach ($contextos_asignables as $contexto)
                                    <option value="{{ $contexto->contexto_id }}">{{ $contexto->nombre }}</option>
                                @endforeach
                            </select>
                            @error('contexto_asignado_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="button" wire:click="asignarContexto"
                            wire:loading.attr="disabled" wire:target="asignarContexto"
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="asignarContexto">Guardar contexto</span>
                            <span wire:loading wire:target="asignarContexto">Guardando...</span>
                        </button>
                    </div>
                </details>
            @endif

            {{-- Plantillas del contexto --}}
            @if (! $evento_selected?->contexto_id)
                <div class="mb-4 p-3 border border-red-300 bg-red-50 rounded-md text-sm text-red-700">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Asigná un contexto al evento para poder emitir sus certificados.
                </div>
            @elseif (! empty($tipos_faltantes))
                <div class="mb-4 p-3 border border-orange-300 bg-orange-50 rounded-md text-sm text-orange-700">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Faltan plantillas en el contexto para: {{ implode(', ', $tipos_faltantes) }}. Cargalas desde "Contextos" antes de emitir.
                </div>
            @else
                <p class="text-sm text-gray-600 mb-3">Se generarán los certificados usando estas plantillas del contexto:</p>
            @endif

            <ul class="space-y-2">
                @foreach ($plantillas_contexto as $info)
                    <li class="flex items-center gap-3 border rounded-xl px-4 py-3 {{ $info['existe'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                        <i class="fa-solid {{ $info['existe'] ? 'fa-circle-check text-green-600' : 'fa-circle-xmark text-red-500' }}"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $info['etiqueta'] }}</p>
                            <p class="text-xs text-gray-500">{{ $info['existe'] ? 'Plantilla: '.$info['nombre'] : 'Sin plantilla configurada' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-slot>

        <div wire:loading wire:target="emitirCertificados" class="flex items-center justify-center py-4">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-600">Generando certificados, por favor espere...</span>
        </div>


        <x-slot name="footer">
            <x-secondary-button wire:click="$set('open_emitir', false)">
                Cancelar
            </x-secondary-button>

            <button type="button" wire:click="emitirCertificados" style="font-size: 0.75rem; font-weight: 600"
                wire:loading.attr="disabled"
                @disabled(! $evento_selected?->contexto_id || ! empty($tipos_faltantes))
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="emitirCertificados">Emitir</span>
                <span wire:loading wire:target="emitirCertificados">Emitiendo...</span>
            </button>
        </x-slot>
    </x-dialog-modal>

    {{-- ------------------------ NUEVO: DIALOG MODAL para Enviar Mails --------------------------- --}}
    <x-dialog-modal wire:model="open_enviar_mail">
        <x-slot name="title">
            Enviar Certificados por Mail
            @if ($evento_selected)
                <span class="text-sm font-normal text-gray-500">- {{ $evento_selected->nombre }}</span>
            @endif
        </x-slot>

        <x-slot name="content">
            <div class="max-h-96 overflow-y-auto pr-2">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="w-10 px-2 py-2">
                                {{-- Checkbox para seleccionar todos (opcional, por ahora desactivado) --}}
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Nombre y Apellido</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Email</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($participantes_mail as $participante)
                            <tr class="hover:bg-gray-50">
                                <td class="w-10 px-2 py-2 text-center">
                                    <input type="checkbox" wire:model.defer="selected_participantes"
                                        value="{{ $participante->participante_id }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                    {{ $participante->nombre }} {{ $participante->apellido }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                    {{ $participante->mail }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500">
                                    No hay participantes en este evento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div wire:loading wire:target="enviarMailsTodos, enviarMailsSeleccionados"
                class="flex items-center justify-center py-4">
                <svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span class="ml-2 text-sm text-gray-600">Enviando correos, por favor espere...</span>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="w-full flex justify-between items-center">
                <!-- Botón izquierdo -->
                <x-secondary-button wire:click="$set('open_enviar_mail', false)">
                    <i class="fa-solid fa-xmark mr-2"></i>
                    Cancelar
                </x-secondary-button>

                <!-- Botones derechos -->
                <div class="flex gap-2">
                    <button wire:click="enviarMailsTodos"
                        class="inline-flex items-center px-2 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                        <i class="fa-solid fa-users mr-2"></i>
                        Enviar a Todos
                    </button>

                    <button wire:click="enviarMailsSeleccionados"
                        class="inline-flex items-center px-2 py-2 bg-brand-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-primary/90 active:bg-brand-primary focus:outline-none focus:border-brand-primary focus:ring focus:ring-brand-accent/50 disabled:opacity-25 transition">
                        <i class="fa-solid fa-user-check mr-2"></i>
                        Solo Seleccionados
                    </button>
                </div>
            </div>
        </x-slot>

    </x-dialog-modal>
</div>