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
                                <i class="fa-solid fa-users fa-xl"></i>

                            </a>
                            <a href="{{ route('inscripcion.evento', [Str::slug($evento->tipoEvento->nombre, '-'), $evento->evento_id]) }}"
                                class="cursor-pointer mx-2" title="Formulario de Inscripción">
                                <i class="fa-solid fa-file-signature fa-xl text-black"></i>
                            </a>
                            <a onclick="confirmFinish({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2"
                                title="Finalizar Evento">
                                <i class="fa-solid fa-check-to-slot fa-xl"></i>
                            </a>
                            <a onclick="confirmCancel({{ $evento }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2" title="Cancelar Evento">
                                <i class="fa-solid fa-times-circle fa-xl"></i>
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

</div>
