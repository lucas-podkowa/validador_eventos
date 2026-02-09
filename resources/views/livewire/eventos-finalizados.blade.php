<div>
    <!-- Buscadores -->
    <div class="flex mb-2 space-x-2">
        <input type="text" wire:model.lazy="search" placeholder="Buscar Evento"
            class="w-1/2 p-2 border border-gray-300 rounded" />

        <input type="text" wire:model.lazy="searchParticipante" placeholder="Buscar Participante por DNI"
            class="w-1/2 p-2 border border-gray-300 rounded" />
    </div>
    <!-- Contenido de Eventos Finalizados -->
    <x-table>
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tipo de Evento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Fecha de Inicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventosFinalizados as $evento)
                    <tr>
                        <td class="px-6 py-3">{{ $evento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->fecha_inicio }}</td>
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
                                        {{-- Mostrar solo alerta si requiere revisión --}}
                                        <div class="flex items-center justify-center text-yellow-600 py-2 px-4">
                                            <i class="fa-solid fa-triangle-exclamation fa-xl mr-2"
                                                title="Requiere revisar Aprobaciones"></i>
                                            Requiere aprobación
                                        </div>
                                    @else
                                        {{-- Mostrar el resto de opciones si ya fue revisado --}}
                                        <a wire:click="detail({{ $evento }})"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="mr-2 fa-solid fa-qrcode fa-xl"></i>
                                            Ver Códigos QR
                                        </a>

                                        <a wire:click="emitir({{ $evento }})"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="mr-2 fa-solid fa-file-pdf fa-xl text-blue-500"></i>
                                            Emitir Certificados
                                        </a>

                                        @if ($evento->certificados_disponibles)
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

                                        {{-- <a wire:click="abrirCarpeta('{{ $evento->certificado_path }}')"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="mr-1 fa-solid fa-folder-open fa-xl"></i>
                                            Descargar Certificados
                                        </a> --}}
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

    {{-- ------------------------  DIALOG MODAL ver QR--------------------------- --}}

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

    {{-- ------------------------  DIALOG MODAL subir plantilla certificado--------------------------- --}}

    <x-dialog-modal wire:model="open_emitir">
        <x-slot name="title">
            <h4 class="text-md font-semibold mb-2 mt-4 text-blue-600">Selector de Plantillas para Certificados</h4>

        </x-slot>

        <x-slot name="content">
            @if ($evento_selected && $evento_selected->por_aprobacion)
                </h4>
                <div class="mb-4">
                    <label for="background_image_asistencia" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-file-lines text-blue-500 mr-1"></i>
                        Plantilla para Certificado de Asistencia (No Aprobados)
                    </label>
                    <input type="file" id="background_image_asistencia" wire:model="background_image_asistencia"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image_asistencia')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="background_image_aprobacion" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-award text-green-600 mr-1"></i>
                        Plantilla para Certificado de Aprobación (Aprobados)
                    </label>
                    <input type="file" id="background_image_aprobacion" wire:model="background_image_aprobacion"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image_aprobacion')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="background_image_disertante" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-chalkboard-user text-purple-600 mr-1"></i>
                        Plantilla para Certificado de Disertante
                    </label>
                    <input type="file" id="background_image_disertante" wire:model="background_image_disertante"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image_disertante')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="background_image_colaborador" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-handshake text-purple-600 mr-1"></i>
                        Plantilla para Certificado de Colaborador
                    </label>
                    <input type="file" id="background_image_colaborador" wire:model="background_image_colaborador"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image_colaborador')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @else
                <div class="mb-4">
                    <label for="background_image" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-user text-indigo-500 mr-1"></i>
                        Plantilla para Certificado de Asistentes
                    </label>
                    <input type="file" id="background_image" wire:model="background_image"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="background_image_disertante" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-chalkboard-user text-purple-600 mr-1"></i>
                        Plantilla para Certificado de Disertante
                    </label>
                    <input type="file" id="background_image_disertante" wire:model="background_image_disertante"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image_disertante')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="background_image_colaborador" class="block text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-handshake text-purple-600 mr-1"></i>
                        Plantilla para Certificado de Colaborador
                    </label>
                    <input type="file" id="background_image_colaborador" wire:model="background_image_colaborador"
                        accept="image/png, image/jpeg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('background_image_colaborador')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @endif
        </x-slot>

        <div wire:loading wire:target="emitirCertificados" class="flex items-center justify-center py-4">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                    stroke-width="4">
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
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                Emitir
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
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4">
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
                        class="inline-flex items-center px-2 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition">
                        <i class="fa-solid fa-user-check mr-2"></i>
                        Solo Seleccionados
                    </button>
                </div>
            </div>
        </x-slot>

    </x-dialog-modal>
</div>
