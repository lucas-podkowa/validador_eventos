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
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium">
                            <button wire:click="detail({{ $evento }})">
                                <i class="fa-solid fa-qrcode fa-xl" title="Ver Códigos QR"></i>
                            </button>
                            {{-- @if (!$evento->certificado_path) --}}
                            <button wire:click="emitir({{ $evento }})">
                                <i class="mx-2 fa-solid fa-file-pdf fa-xl text-blue-500"
                                    title="Emitir Certificados"></i>
                            </button>
                            {{-- @endif --}}
                            @if ($evento->certificado_path)
                                <button wire:click="abrirCarpeta('{{ $evento->certificado_path }}')">
                                    <i class="mx-2 fa-solid fa-folder-open fa-xl" title="Descargar Certificados"></i>
                                </button>
                            @endif

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
            Selector de Plantilla para Certificados
        </x-slot>

        <x-slot name="content">
            <div class="w-full">
                <label for="background_image" class="block text-sm font-medium text-gray-700">I Selector de Plantilla
                    para Certificados</label>
                <input type="file" id="background_image" wire:model="background_image"
                    accept="image/png, image/jpeg, image/jpg"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('background_image')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

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
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                Emitir
            </button>


        </x-slot>
    </x-dialog-modal>
</div>
