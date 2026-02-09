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
                        Fecha de Inicio
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Revisor
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Periodo de Inscripción
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        QR Formulario
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">
                        Inscriptos
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($eventos as $evento)
                    <tr>
                        <td class="px-6 py-2">
                            <i class="fa solid fa-info-circle text-blue-500 mr-1 cursor-pointer"
                                wire:click="verDetalles('{{ $evento->evento_id }}')"></i> {{ $evento->nombre }}
                        </td>
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

                        <td class="item-center px-6">
                            @if ($evento->planillaInscripcion && $evento->planillaInscripcion->qr_formulario)
                                <img src="{{ $evento->planillaInscripcion->qr_formulario }}" alt="QR"
                                    width="50" height="50" class="cursor-pointer" />
                            @else
                                No definido
                            @endif
                        </td>

                        <!-- Nueva columna de Inscriptos con icono de ojo -->
                        <td class="px-6 py-2 text-center">
                            <button wire:click="get_inscriptos({{ $evento }})"
                                class="text-blue-600 hover:text-blue-800 transition" title="Ver Inscriptos">
                                <i class="fa-solid fa-eye fa-lg"></i>
                            </button>
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

                                    <a wire:click.prevent="get_staff({{ $evento }})"
                                        class="block px-4 py-1 text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                        <i class="fa-solid fa-user-tie fa-xl"></i> Staff
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

                                    @can('crear_eventos')
                                        <a href="{{ route('asignar_gestores', ['evento_id' => $evento->evento_id]) }}"
                                            class="block px-4 py-1 text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                            style="text-decoration: none; color: inherit;">
                                            <i class="fa-regular fa-user fa-xl "></i>
                                            Editar Gestores
                                        </a>
                                    @endcan
                                    @if ($evento->por_aprobacion)
                                        <a wire:click="modalRevisor('{{ addslashes($evento->evento_id) }}')"
                                            class="block px-4 py-1 text-blue-600 hover:bg-gray-100 cursor-pointer flex items-center gap-2">
                                            <i class="fas fa-user-check fa-xl"></i>Asignar Revisor
                                        </a>
                                    @endif

                                    <hr class="border-gray-200">

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

    <!-- Modal Revisor -->
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

    <!-- Tabla de Inscriptos (solo Asistentes) -->
    @if ($evento_selected && $mostrar_inscriptos)
        <h3 class="mt-6 text-lg font-semibold">Inscriptos en {{ $evento_selected->nombre }}</h3>

        <!-- Botones: Importar Participantes a la izquierda, exportar PDF y CSV a la derecha -->
        <div class="flex justify-between items-center mb-4 mt-3">
            <a href="{{ route('importar_participantes', ['evento_id' => $evento_selected->evento_id]) }}"
                class="border border-green-600 text-green-600 px-4 py-2 rounded-xl text-sm transition hover:bg-green-50"
                style="text-decoration: none;">
                <i class="fa-solid fa-file-import"></i> Importar Participantes
            </a>

            <div class="flex space-x-2">
                <button wire:click="exportarPDF" @if (!count($inscriptos)) disabled @endif
                    class="border border-red-600 text-red-600 px-2 py-1 rounded-xl text-sm transition {{ count($inscriptos) ? 'hover:bg-red-50' : 'opacity-50 cursor-not-allowed' }}">
                    📄 PDF
                </button>

                <button wire:click="descargarCSV" @if (!count($inscriptos)) disabled @endif
                    class="border border-blue-600 text-blue-600 px-2 py-1 rounded-xl text-sm transition {{ count($inscriptos) ? 'hover:bg-blue-50' : 'opacity-50 cursor-not-allowed' }}">
                    📥 CSV
                </button>
            </div>
        </div>

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
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($inscriptos as $inscripto)
                        <tr>
                            <td class="px-6 whitespace-nowrap">{{ $inscripto->participante->nombre }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $inscripto->participante->apellido }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $inscripto->participante->dni }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $inscripto->participante->mail }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $inscripto->participante->telefono }}</td>
                            <td class="px-6 text-center whitespace-nowrap">
                                <button onclick="confirmUnregister('{{ $inscripto->inscripcion_participante_id }}')"
                                    class="text-red-600 hover:text-red-900" title="Desmatricular">
                                    <i class="fas fa-user-times"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-4">Aún no se han registrado Asistentes</div>
        @endif
    @endif

    <!-- Tabla de Disertantes y Colaboradores -->
    @if ($evento_selected && $mostrar_disertantes_colaboradores)
        <h3 class="mt-6 text-lg font-semibold">Disertantes y Colaboradores en {{ $evento_selected->nombre }}</h3>

        <div class="flex justify-between items-center mb-4 mt-3">
            <!-- Botón para inscribir (enlace a la nueva página) -->
            <a href="{{ route('inscribir.staff', ['evento_id' => $evento_selected->evento_id]) }}"
                class="border border-green-600 text-green-600 px-4 py-2 rounded-xl text-sm transition hover:bg-green-50 flex items-center gap-2"
                style="text-decoration: none;">
                <i class="fa-solid fa-user-plus"></i> Agregar Disertante o Colaborador
            </a>

            <div class="flex-1"></div>
        </div>

        <!-- Campo de búsqueda -->
        <div class="mb-4 mt-3">
            <input type="text" wire:model.debounce.300ms="searchDisertante"
                class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
                placeholder="Buscar por nombre, apellido o DNI...">
        </div>

        @if (count($disertantes_colaboradores))
            <table class="w-full min-w-full divide-y divide-gray-200 mt-2">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Apellido</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">DNI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Teléfono</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($disertantes_colaboradores as $persona)
                        <tr>
                            <td class="px-6 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs rounded {{ $persona->rol->nombre == 'Disertante' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $persona->rol->nombre }}
                                </span>
                            </td>
                            <td class="px-6 whitespace-nowrap">{{ $persona->participante->nombre }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $persona->participante->apellido }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $persona->participante->dni }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $persona->participante->mail }}</td>
                            <td class="px-6 whitespace-nowrap">{{ $persona->participante->telefono }}</td>
                            <td class="px-6 text-center whitespace-nowrap">
                                <button onclick="confirmUnregister('{{ $persona->inscripcion_participante_id }}')"
                                    class="text-red-600 hover:text-red-900" title="Desmatricular">
                                    <i class="fas fa-user-times"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-4">No hay Disertantes ni Colaboradores registrados</div>
        @endif
    @endif


    <!-- Modal Detalles -->
    <x-dialog-modal wire:model="open_modal_detalles">
        <x-slot name="title">
            Detalles del Evento
        </x-slot>

        <x-slot name="content">
            @if ($evento_detalles)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700">

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border col-span-1 sm:col-span-2">
                        <p class="text-gray-500 text-xs uppercase">Nombre</p>
                        <p class="mb-0 font-semibold">{{ $evento_detalles->nombre }}</p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Fecha de Inicio</p>
                        <p class="mb-0 font-semibold">{{ $evento_detalles->fecha_inicio_formatted }}</p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Tipo de Evento</p>
                        <p class="mb-0 font-semibold">{{ $evento_detalles->tipoEvento->nombre ?? 'N/A' }}</p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Lugar</p>
                        <p class="mb-0 font-semibold">{{ $evento_detalles->lugar }}</p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Certificación</p>
                        <p class="mb-0 font-semibold">
                            {{ $evento_detalles->por_aprobacion ? 'Por Aprobación' : 'Por Asistencia' }}
                        </p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Revisor</p>
                        <p class="mb-0 font-semibold">
                            @if ($evento_detalles->por_aprobacion)
                                {{ $evento_detalles->revisor->name ?? 'No asignado' }}
                            @else
                                No Requiere
                            @endif
                        </p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Gestores</p>
                        <p class="mb-0 font-semibold">
                            @if ($evento_detalles->gestores->isEmpty())
                                Sin Asignar
                            @else
                                {{ $evento_detalles->gestores->pluck('name')->join(', ') }}
                            @endif
                        </p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Cupo</p>
                        <p class="mb-0 font-semibold">{{ $evento_detalles->cupo }}</p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Inscriptos</p>
                        <p class="mb-0 font-semibold">
                            {{ $evento_detalles->asistentesInscritos()->count() }} (Participantes)</p>
                        <p class="mb-0 font-semibold">
                            {{ $evento_detalles->disentantesYColaboradores()->count() }} (Disertantes y/o
                            Colaboradores)</p>
                    </div>


                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Inicio Inscripción</p>
                        <p class="mb-0 font-semibold">
                            {{ $evento_detalles->planillaInscripcion->apertura
                                ? \Carbon\Carbon::parse($evento_detalles->planillaInscripcion->apertura)->format('d/m/Y H:i')
                                : 'N/A' }}
                        </p>
                    </div>

                    <div class="w-full bg-gray-50 p-2 rounded-xl shadow-sm border">
                        <p class="text-gray-500 text-xs uppercase">Fin Inscripción</p>
                        <p class="mb-0 font-semibold">
                            {{ $evento_detalles->planillaInscripcion->cierre
                                ? \Carbon\Carbon::parse($evento_detalles->planillaInscripcion->cierre)->format('d/m/Y H:i')
                                : 'N/A' }}
                        </p>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('open_modal_detalles', false)">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

</div>
