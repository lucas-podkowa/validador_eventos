<div>
    <x-table>
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="bg-gray-200">
                    <th wire:click="order('nombre')" class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Nombre
                        @if ($sort === 'nombre')
                            @if ($direction === 'asc')
                                <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                            @endif
                        @else
                            <i class="fas fa-sort float-right mt-1"></i>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tipo de Evento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Fecha de Inicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Lugar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Cupo</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventos as $evento)
                    <tr>
                        <td class="px-6 py-3">{{ $evento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->fecha_inicio_formatted }}</td>
                        <td class="px-6 py-3">{{ $evento->lugar }}</td>
                        <td class="px-6 py-3">{{ $evento->cupo ?: 'Sin Límites' }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-right sticky right-0 bg-white">

                            <a href="{{ route('registrar_evento', ['evento_id' => $evento->evento_id]) }}"
                                class="cursor-pointer mx-2" title="Editar">
                                <i class="fa-solid fa-edit fa-xl text-black"></i>
                            </a>

                            <a wire:click="duplicarEvento({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2" title="Clonar Evento">
                                <i class="fa-regular fa-clone fa-xl  "></i>
                            </a>
                            <a wire:click="show_dialog_planilla({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2"
                                title="Habilitar Inscripción">
                                <i class="fa-regular fa-clipboard fa-xl text-blue-500 "></i>
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
                    <div class="mb-4">
                        <label for="apertura" class="block text-gray-700">Fecha y Hora de Apertura</label>
                        <input type="datetime-local" id="apertura" wire:model="apertura"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>

                    <div class="mb-4">
                        <label for="cierre" class="block text-gray-700">Fecha y Hora de Cierre</label>
                        <input type="datetime-local" id="cierre" wire:model="cierre"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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

                <button type="submit" wire:loading.attr="disabled" style="font-size: 0.75rem; font-weight: 600"
                    class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                    <span wire:loading.remove>Habilitar</span>
                    <span wire:loading>Procesando...</span>
                </button>
            </x-slot>

        </x-dialog-modal>
    </form>

</div>
