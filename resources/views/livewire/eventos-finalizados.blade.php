<div>
    <!-- Buscador -->
    <div class="mb-2">
        <input type="text" wire:model.lazy="search" placeholder="Buscar evento..."
            class="w-full p-2 border border-gray-300 rounded">
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
                        {{-- <td class="px-6 py-3 whitespace-nowrap text-sm font-medium">
                            <a wire:click="detail({{ $evento->evento_id }})">Detalle</a>
                        </td> --}}
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium">
                            <button wire:click="detail({{ $evento }})">
                                <i class="fa-solid fa-qrcode fa-xl"></i>
                            </button>
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

</div>
