<div class="px-4 sm:px-6 lg:px-8 py-4">
    <h2 class="text-xl font-bold mb-4">Solicitudes de corrección de DNI</h2>

    <div class="mb-4 w-full md:w-64">
        <select wire:model.live="estado" class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
            <option value="pendiente">Pendientes</option>
            <option value="aprobada">Aprobadas</option>
            <option value="rechazada">Rechazadas</option>
            <option value="todas">Todas</option>
        </select>
    </div>

    @if ($solicitudes->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="bg-gray-200">
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Participante</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">DNI actual</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">DNI solicitado</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Fecha</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($solicitudes as $solicitud)
                        <tr>
                            <td class="px-4 py-2">
                                {{ $solicitud->participante->apellido ?? '' }}, {{ $solicitud->participante->nombre ?? '' }}
                            </td>
                            <td class="px-4 py-2">{{ $solicitud->dni_actual }}</td>
                            <td class="px-4 py-2 font-semibold">{{ $solicitud->dni_solicitado }}</td>
                            <td class="px-4 py-2">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ ucfirst($solicitud->estado) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                <button class="btn-action-edit" title="Ver" wire:click="ver({{ $solicitud->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>

        <div class="py-4">{{ $solicitudes->links() }}</div>
    @else
        <div class="text-gray-500">No hay solicitudes para mostrar.</div>
    @endif

    <x-dialog-modal wire:model="open_modal">
        <x-slot name="title">Solicitud de corrección de DNI</x-slot>

        <x-slot name="content">
            @if ($solicitud_selected)
                <div class="space-y-3 text-sm">
                    <p>
                        <strong>Participante:</strong>
                        {{ $solicitud_selected->participante->apellido ?? '' }},
                        {{ $solicitud_selected->participante->nombre ?? '' }}
                    </p>
                    <p>
                        <strong>DNI actual:</strong> {{ $solicitud_selected->dni_actual }}
                        &nbsp;→&nbsp;
                        <strong>DNI solicitado:</strong> {{ $solicitud_selected->dni_solicitado }}
                    </p>
                    @if ($solicitud_selected->motivo)
                        <p><strong>Motivo:</strong> {{ $solicitud_selected->motivo }}</p>
                    @endif

                    <div>
                        <p class="mb-1"><strong>Documento adjunto:</strong></p>
                        <a href="{{ route('admin.solicitudes_dni.imagen', $solicitud_selected) }}" target="_blank"
                            class="text-brand-primary underline">
                            Ver imagen ({{ $solicitud_selected->imagen_original }})
                        </a>
                    </div>

                    @if ($solicitud_selected->estado === 'pendiente')
                        <div>
                            <label class="block font-semibold mb-1">Observación (opcional)</label>
                            <textarea wire:model="observacion" rows="3"
                                class="w-full rounded border-gray-300"></textarea>
                            @error('observacion')
                                <span class="text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    @else
                        <p><strong>Estado:</strong> {{ ucfirst($solicitud_selected->estado) }}</p>
                        @if ($solicitud_selected->observacion)
                            <p><strong>Observación:</strong> {{ $solicitud_selected->observacion }}</p>
                        @endif
                    @endif
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <div class="flex">
                <x-secondary-button wire:click="$set('open_modal', false)">Cerrar</x-secondary-button>

                @if ($solicitud_selected && $solicitud_selected->estado === 'pendiente')
                    <button type="button" wire:click="rechazar"
                        class="mx-2 rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase text-white">
                        Rechazar
                    </button>
                    <button type="button" wire:click="aprobar"
                        class="rounded-md bg-brand-primary px-4 py-2 text-xs font-semibold uppercase text-white">
                        Aprobar y re-emitir
                    </button>
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
