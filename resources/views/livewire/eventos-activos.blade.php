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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Revisor
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Periodo de Inscripción
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventos as $evento)
                    <tr>
                        <td class="px-6 py-2">{{ $evento->nombre }}</td>
                        <td class="px-6 py-2">{{ $evento->tipoEvento->nombre }}</td>
                        <td class="px-6 py-2">{{ $evento->fecha_inicio_formatted }}</td>
                        <td>
                            @if ($evento->por_aprobacion)
                                {{ $evento->revisor ? $evento->revisor->name : 'No asignado' }}
                            @else
                                No Requiere
                            @endif
                        </td>
                        <td>
                            @if ($evento->planillaInscripcion)
                                {{ \Carbon\Carbon::parse($evento->planillaInscripcion->apertura)->format('d/m/Y') }}
                                -
                                {{ \Carbon\Carbon::parse($evento->planillaInscripcion->cierre)->format('d/m/Y') }}
                            @else
                                No definida
                            @endif
                        </td>

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
                                    <a href="{{ route('editar_planilla', ['evento_id' => $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa-solid fa-calendar-alt fa-xl"></i> Editar Planilla
                                    </a>

                                    <hr class="border-gray-200">
                                    @if ($evento->por_aprobacion)
                                        <a wire:click="modalRevisor('{{ addslashes($evento->evento_id) }}')"
                                            class="block px-4 py-1 text-blue-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="fas fa-user-check fa-xl"></i>Asignar Revisor
                                        </a>
                                    @endif

                                    <a href="{{ route('registrar_evento', ['evento_id' => $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa-solid fa-edit fa-xl text-black"></i> Editar Evento
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
    <x-dialog-modal wire:model="open_modal_revisor">
        <x-slot name="title">
            Asignar Revisor al Evento
        </x-slot>

        <x-slot name="content">
            <div>
                <input type="text" wire:model.live="busqueda_usuario" class="w-full rounded border-gray-300"
                    placeholder="Buscar por nombre o email...">
            </div>

            <div class="mt-4">
                @forelse ($usuarios_filtrados as $usuario)
                    <label class="flex items-center space-x-2 mb-2">
                        <input type="radio" wire:model="usuario_seleccionado_id" value="{{ $usuario->id }}">
                        <span>{{ $usuario->name }} - {{ $usuario->email }}</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Sin resultados.</p>
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

    <!-- Modal -->



</div>
