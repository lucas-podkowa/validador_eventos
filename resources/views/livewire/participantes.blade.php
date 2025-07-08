<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <h2 class="text-xl font-bold mb-4">Gestión de Participantes</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 text-green-700 p-2 mb-4 rounded">
            {{ session('message') }}
        </div>
    @endif

    <!-- Campo de búsqueda -->
    <div class="mb-4">
        <input type="text" wire:model.live="searchParticipante"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar por nombre, apellido o DNI...">
    </div>
    <x-table>

        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="bg-gray-200">
                    <th class="px-4 py-3 text-xs font-medium border text-gray-500">Apellido</th>
                    <th class="px-4 py-3 text-xs font-medium border text-gray-500">Nombre</th>
                    <th class="px-4 py-3 text-xs font-medium border text-gray-500">DNI</th>
                    <th class="px-4 py-3 text-xs font-medium border text-gray-500">Correo</th>
                    <th class="px-4 py-3 text-xs font-medium border text-gray-500">Teléfono</th>
                    <th class="px-4 py-3 text-xs font-medium border text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($participantes as $participante)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $participante->apellido }}</td>
                        <td class="px-4 py-2">{{ $participante->nombre }}</td>
                        <td class="px-4 py-2">{{ $participante->dni }}</td>
                        <td class="px-4 py-2">{{ $participante->mail }}</td>
                        <td class="px-4 py-2">{{ $participante->telefono }}</td>
                        <td class="px-4 py-2">
                            <a wire:click="edit('{{ $participante->participante_id }}')" class="cursor-pointer mx-2"
                                title="Editar">
                                <i class="fa-solid fa-edit text-black"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>


    <div class="mt-4">
        {{ $participantes->links() }}
    </div>

    <!-- Modal -->
    <x-dialog-modal wire:model="open_modal">
        <x-slot name="title">Editar Participante</x-slot>
        <x-slot name="content">

            <div class="flex pt-4 gap-4">

                <div class="flex-1 mb-4">
                    <label class="block">Apellido:</label>
                    <input type="text" wire:model="apellido" class="w-full rounded p-2">
                    @error('apellido')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex-1 mb-4">
                    <label class="block">Nombre/s:</label>
                    <input type="text" wire:model="nombre" class="w-full rounded p-2">
                    @error('nombre')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

            </div>
            <div class="mb-4">
                <label class="block">DNI:</label>
                <input type="text" wire:model="dni" class="w-full border rounded p-2">
                @error('dni')
                    <span class="text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block">Correo Electrónico:</label>
                <input type="email" wire:model="mail" class="w-full border rounded p-2">
                @error('mail')
                    <span class="text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block">Teléfono:</label>
                <input type="text" wire:model="telefono" class="w-full border rounded p-2">
                @error('telefono')
                    <span class="text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex">
                <x-secondary-button wire:click="$set('open_modal', false)">
                    Volver
                </x-secondary-button>
                <button type="button" wire:click="update" style="font-size: 0.75rem; font-weight: 600"
                    class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                    Actualizar
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
