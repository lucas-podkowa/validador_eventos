<div>
    <x-table>
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Tipo de Evento
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Fecha de Inicio
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventos as $evento)
                    <tr>
                        <td class="px-6 py-2">{{ $evento->nombre }}</td>
                        <td class="px-6 py-2">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-2">{{ $evento->fecha_inicio_formatted }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium relative overflow-visible">
                            <div x-data="{ open: false }">
                                <button @click="open = !open"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md focus:outline-none flex items-center">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>

                                <div x-show="open" @click.away="open = false"
                                    class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg"
                                    style="z-index: 9999;">
                                    <a wire:click="get_inscriptos({{ $evento }})"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="fa-solid fa-users fa-xl"></i> Ver Inscritos
                                    </a>
                                    <a href="{{ route('inscripcion.evento', [Str::slug($evento->tipoEvento->nombre, '-'), $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa fa-address-card fa-xl"></i> Formulario de Inscripción
                                    </a>
                                    <a href="{{ route('planilla.editar', ['evento_id' => $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa-solid fa-calendar-alt fa-xl"></i> Editar Planilla
                                    </a>

                                    <hr class="border-gray-200">
                                    <a href="{{ route('registrar_evento', ['evento_id' => $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa-solid fa-edit fa-xl text-black"></i>Editar Evento
                                    </a>
                                    <a onclick="confirmFinish('{{ addslashes($evento->evento_id) }}')"
                                        class="block px-4 py-1 text-blue-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="fa-solid fa-check-to-slot fa-xl"></i> Finalizar Evento
                                    </a>
                                    <a onclick="confirmCancel('{{ addslashes($evento->evento_id) }}')"
                                        class="block px-4 py-1 text-red-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="fa-solid fa-times-circle fa-xl"></i> Cancelar Evento
                                    </a>

                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>


    @if ($evento_selected && $mostrar_inscriptos)
        <h3 class="mt-4 text-lg font-semibold">Inscriptos en {{ $evento_selected->nombre }}</h3>
        <!-- Campo de búsqueda -->
        <div class="mb-4">
            <input type="text" wire:model.debounce.300ms="searchParticipante"
                class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
                placeholder="Buscar por nombre, apellido o DNI...">
        </div>
        @if (count($inscriptos))
            <table class="w-full min-w-full divide-y divide-gray-200 mt-2">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Apellido</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">DNI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Teléfono</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($inscriptos as $inscripto)
                        <tr>
                            <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->nombre }}</td>
                            <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->apellido }}</td>
                            <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->dni }}</td>
                            <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->mail }}</td>
                            <td class="px-6  whitespace-nowrap">{{ $inscripto->participante->telefono }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-4">Aún no se han registrado Participantes</div>
        @endif
    @endif

</div>
