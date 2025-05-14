<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session()->has('success'))
        <div class="bg-green-200 text-green-800 p-2 mb-2">{{ session('success') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-200 text-red-800 p-2 mb-2">{{ session('error') }}</div>
    @endif

    <h2 class="text-xl font-bold mb-4">Eventos por aprobación no procesados</h2>

    <x-table>
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="bg-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Inscriptos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Asistentes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($eventos as $evento)
                    <tr>
                        <td class="px-6 py-3">{{ $evento->nombre }}</td>
                        <td class="px-6 py-3">{{ $evento->fecha_inicio }}
                        </td>
                        <td class="px-6 py-3">
                            {{ $evento->inscriptos()->count() }}</td>
                        <td class="px-6 py-3">
                            {{ $evento->participantesConAsistencia()->count() }}</td>
                        <td class="px-6 py-3">

                            <a wire:click="seleccionarEvento('{{ $evento->evento_id }}')"
                                class="bg-blue-500 text-white px-2 py-1" title="Procesar">
                                <i class="fa-solid fa-eye fa-xl text-black"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>

    @if ($eventoSeleccionado)
        <h3 class="mt-4 text-lg font-semibold mb-2">Participantes del evento: {{ $eventoSeleccionado->nombre }}</h3>

        <form wire:submit.prevent="guardar">
            <table class="w-full border mb-4">
                <thead>
                    <tr>
                        <th class="border px-2">Nombre</th>
                        <th class="border px-2">Apellido</th>
                        <th class="border px-2">DNI</th>
                        <th class="border px-2">Aprobado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($participantes as $index => $p)
                        <tr>
                            <td class="border px-2">{{ $p->participante->nombre }}</td>
                            <td class="border px-2">{{ $p->participante->apellido }}</td>
                            <td class="border px-2">{{ $p->participante->dni }}</td>
                            <td class="border px-2 text-center">
                                <input type="checkbox"
                                    wire:change="actualizarEstado({{ $index }}, $event.target.checked)"
                                    {{ $estadoAprobacion[$p->evento_participantes_id] ? 'checked' : '' }}>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" style="font-size: 0.75rem; font-weight: 600"
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                Guardar Aprobaciones
            </button>

        </form>
    @endif
</div>
