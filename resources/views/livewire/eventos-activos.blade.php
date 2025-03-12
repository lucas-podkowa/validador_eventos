<div>
    <x-table>
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tipo de Evento
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Fecha de Inicio
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Partipantes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventosEnCurso as $evento)
                    <tr
                        class="{{ $evento_selected && $evento_selected->evento_id == $evento->evento_id ? 'bg-blue-600' : '' }}">
                        <td class="px-6 py-2">{{ $evento->nombre }}</td>
                        <td class="px-6 py-2">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-2">{{ $evento->fecha_inicio_formatted }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                            <a wire:click="get_inscriptos({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2" title="Ver Incriptos">
                                <i class="fa-solid fa-users fa-xl  text-black"></i>

                            </a>
                            <a href="{{ route('inscripcion.evento', [Str::slug($evento->tipoEvento->nombre, '-'), $evento->evento_id]) }}"
                                class="cursor-pointer mx-2" title="Formulario de Inscripción">
                                <i class="fa fa-address-card fa-xl text-black"></i>
                            </a>
                            <a wire:click="showEditModal({{ $evento }})" class="cursor-pointer mx-2"
                                title="Editar Fechas">
                                <i class="fa-solid fa-calendar-alt fa-xl text-black"></i>
                            </a>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">


                            <a onclick="confirmFinish({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2"
                                title="Finalizar Evento">
                                <i class="fa-solid fa-check-to-slot fa-xl  text-blue-500"></i>
                            </a>
                            <a onclick="confirmCancel({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2" title="Cancelar Evento">
                                <i class="fa-solid fa-times-circle fa-xl  text-red-500"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>

    <!-- Tabla de Participantes -->
    @if ($evento_selected)
        <h3 class="mt-4 text-lg font-semibold">Participantes del Evento</h3>
        <!-- Campo de búsqueda -->
        <div class="mb-4">
            <input type="text" wire:model.debounce.300ms="searchParticipante"
                class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
                placeholder="Buscar por nombre, apellido o DNI...">
        </div>
        <table class="w-full min-w-full divide-y divide-gray-200 mt-2">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Apellido</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">DNI</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500"> Asistencia </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($inscriptos as $inscripto)
                    <tr>
                        <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->nombre }}</td>
                        <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->apellido }}</td>
                        <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->dni }}</td>
                        <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->mail }}</td>
                        <td class="px-6">
                            <input type="checkbox"
                                wire:click="toggleAsistencia({{ $inscripto->inscripcion_participante_id }})"
                                {{ $inscripto->asistencia ? 'checked' : '' }}>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <x-dialog-modal wire:model="open_edit_modal">
        <x-slot name="title">
            Inscripción al Evento {{ $evento_selected->nombre ?? '' }}
        </x-slot>

        <x-slot name="content">
            <div class="flex pt-4 px-6 gap-4">
                <div class="w-1/2">

                    <label for="apertura_edit" class="block text-sm font-medium text-gray-700">Fecha de Apertura</label>
                    <input type="date" id="apertura_edit" wire:model.live="apertura"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('apertura')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="w-1/2">
                    <label for="cierre_edit" class="block text-sm font-medium text-gray-700">Fecha de Cierre</label>
                    <input type="date" id="cierre_edit" wire:model.live="cierre"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('cierre')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mx-2" wire:click="$set('open_edit_modal', false)">Cancelar</x-secondary-button>
            <x-button class="mx-2" wire:click="updateFechas">Actualizar</x-button>
        </x-slot>
    </x-dialog-modal>


</div>
