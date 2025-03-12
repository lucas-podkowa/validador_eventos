<div>

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
                @foreach ($eventosPendientes as $evento)
                    <tr>
                        <td class="px-6 py-3">{{ $evento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->fecha_inicio_formatted }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium">

                            <a href="{{ route('registrar_evento', ['evento_id' => $evento->evento_id]) }}"
                                class="btn fa-xl fa-regular" title="Editar">
                                <i class="fa-solid fa-edit text-black"></i>
                            </a>
                            <a wire:click="show_dialog_planilla({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2"
                                title="Habilitar Inscripción">
                                <i class="fa-regular fa-clipboard fa-xl  text-blue-500"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>

    {{-- ------------------------  DIALOG MODAL habilitar_planilla--------------------------- --}}
    <form wire:submit.prevent="habilitar_planilla" enctype="multipart/form-data" class="space-y-4">
        <x-dialog-modal wire:model="open_planilla">

            <x-slot name="title" class="bg-gray-900">
                @if ($evento_selected)
                    Habilitar Inscripciones al Evento: {{ $evento_selected->nombre }}
                @endif
            </x-slot>

            <x-slot name="content">
                <div class="flex pt-4 px-6 gap-4">
                    <!-- Fecha del evento -->
                    <div class="w-1/2">
                        <label for="apertura" class="block text-sm font-medium text-gray-700">Apertura</label>
                        <input type="date" id="apertura" wire:model.live="apertura"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('apertura')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="w-1/2">
                        <label for="cierre" class="block text-sm font-medium text-gray-700">Cierre</label>
                        <input type="date" id="cierre" wire:model.live="cierre"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('cierre')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="flex flex-col gap-4 pt-4 px-6">
                    <div class="w-full">
                        <label for="header" class="block text-sm font-medium text-gray-700">Imagen de
                            Cabecera</label>
                        <input type="file" id="header" wire:model="header"
                            accept="image/png, image/jpeg, image/jpg"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('header')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="w-full">
                        <label for="footer" class="block text-sm font-medium text-gray-700">Imagen de Pie</label>
                        <input type="file" id="footer" wire:model="footer"
                            accept="image/png, image/jpeg, image/jpg"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('footer')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </x-slot>


            <x-slot name="footer">

                <x-secondary-button wire:click="$set('open_planilla', false)">
                    Volver
                </x-secondary-button>

                <x-button wire:loading.attr="disabled"
                    class="gap-4 py-2 px-4 mx-4 border border-transparent shadow-sm text-md font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <!-- Mostrar un mensaje de procesamiento mientras se ejecuta -->
                    <span wire:loading.remove>Habilitar</span>
                    <span wire:loading>Procesando...</span>
                </x-button>


            </x-slot>

        </x-dialog-modal>
    </form>

</div>
