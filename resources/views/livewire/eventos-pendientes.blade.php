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
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium relative overflow-visible">
                            <div x-data="{ open: false }">
                                <button @click="open = !open"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md focus:outline-none flex items-center">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>

                                <div x-show="open" @click.away="open = false"
                                    class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg"
                                    style="z-index: 9999;">

                                    <a href="{{ route('registrar_evento', ['evento_id' => $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa-solid fa-edit fa-xl text-black"></i>
                                        Editar
                                    </a>
                                    <a wire:click="duplicarEvento({{ $evento }})"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="fa-regular fa-clone fa-xl  "></i>
                                        Clonar Evento
                                    </a>

                                    <a href="{{ route('habilitar_planilla', ['evento_id' => $evento->evento_id]) }}"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        style="text-decoration: none; color: inherit;">
                                        <i class="fa-regular fa-clipboard fa-xl"></i>
                                        Habilitar Inscripción
                                    </a>
                                    @can('crear_eventos')
                                        <a href="{{ route('asignar_gestores', ['evento_id' => $evento->evento_id]) }}"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                            style="text-decoration: none; color: inherit;">
                                            <i class="fa-regular fa-user fa-xl "></i>
                                            Asignar Gestores
                                        </a>
                                    @endcan


                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>
</div>
