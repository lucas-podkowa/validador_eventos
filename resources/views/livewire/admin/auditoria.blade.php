<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold">Auditoría de acciones</h2>
            <p class="text-sm text-gray-500 mt-1">
                Registro de las operaciones realizadas por los usuarios del sistema.
            </p>
        </div>

        @if ($search || $usuario_id || $accion || $modulo || $desde || $hasta)
            <button type="button" wire:click="limpiarFiltros"
                class="text-sm text-blue-600 hover:underline">
                Limpiar filtros
            </button>
        @endif
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
        <input type="text" wire:model.live.debounce.400ms="search"
            class="w-full px-3 py-2 border rounded-md focus:ring focus:ring-blue-300 sm:col-span-1 lg:col-span-6"
            placeholder="Buscar por descripción, usuario, evento o dato modificado...">

        <select wire:model.live="usuario_id" class="w-full px-3 py-2 border rounded-md lg:col-span-3">
            <option value="">Todos los usuarios</option>
            @foreach ($usuarios as $usuario)
                <option value="{{ $usuario->id }}">
                    {{ $usuario->name }}{{ $usuario->email ? ' ('.$usuario->email.')' : '' }}
                </option>
            @endforeach
        </select>

        <select wire:model.live="accion" class="w-full px-3 py-2 border rounded-md lg:col-span-3">
            <option value="">Todas las acciones</option>
            @foreach ($acciones as $evento)
                <option value="{{ $evento }}">{{ $this->etiquetaAccion($evento) }}</option>
            @endforeach
        </select>

        <select wire:model.live="modulo" class="w-full px-3 py-2 border rounded-md lg:col-span-4">
            <option value="">Todos los módulos</option>
            @foreach ($modulos as $logName)
                <option value="{{ $logName }}">{{ $this->etiquetaModulo($logName) }}</option>
            @endforeach
        </select>

        <div class="flex gap-2 lg:col-span-8">
            <input type="date" wire:model.live="desde" class="w-full px-3 py-2 border rounded-md" title="Desde">
            <input type="date" wire:model.live="hasta" class="w-full px-3 py-2 border rounded-md" title="Hasta">
        </div>
    </div>

    @if ($actividades->count() > 0)
        <div class="overflow-x-auto bg-white shadow border-b border-gray-200 sm:rounded-lg">
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Fecha</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Usuario</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Acción</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Módulo</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Descripción</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">IP</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Detalle</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($actividades as $actividad)
                        @php
                            $nombreCauser = $actividad->causer?->name
                                ?? $actividad->getProperty('causer_name')
                                ?? 'Sistema';
                            $emailCauser = $actividad->causer?->email
                                ?? $actividad->getProperty('causer_email');
                        @endphp
                        <tr wire:key="actividad-{{ $actividad->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-600 whitespace-nowrap">
                                {{ $actividad->created_at?->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <p class="font-medium text-gray-800">{{ $nombreCauser }}</p>
                                @if ($emailCauser)
                                    <p class="text-xs text-gray-500">{{ $emailCauser }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <span class="inline-block text-xs px-2 py-1 rounded-full {{ $this->claseAccion($actividad->event) }}">
                                    {{ $this->etiquetaAccion($actividad->event) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ $this->etiquetaModulo($actividad->log_name) }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ \Illuminate\Support\Str::limit($actividad->description, 70) }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">
                                {{ $actividad->getProperty('ip', '—') }}
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button type="button" wire:click="verDetalle({{ $actividad->id }})"
                                    class="btn-action-edit" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="py-4">
            {{ $actividades->links() }}
        </div>
    @else
        <div class="text-gray-500 py-4">No se encontraron registros de auditoría.</div>
    @endif

    <x-dialog-modal wire:model="open_detalle" maxWidth="5xl">
        <x-slot name="title">
            Detalle de la acción
        </x-slot>

        <x-slot name="content">
            @if ($detalle)
                @php
                    $cambios = $detalle->attribute_changes?->toArray() ?? [];
                    $anteriores = $cambios['old'] ?? [];
                    $nuevos = $cambios['attributes'] ?? [];
                    $claves = array_values(array_unique(array_merge(array_keys($anteriores), array_keys($nuevos))));
                    $propiedades = $detalle->properties?->toArray() ?? [];
                    $nombreCauser = $detalle->causer?->name
                        ?? $detalle->getProperty('causer_name')
                        ?? 'Sistema';
                    $emailCauser = $detalle->causer?->email
                        ?? $detalle->getProperty('causer_email');
                @endphp

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Fecha</p>
                        <p class="text-sm text-gray-800">{{ $detalle->created_at?->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Usuario</p>
                        <p class="text-sm text-gray-800">{{ $nombreCauser }}</p>
                        @if ($emailCauser)
                            <p class="text-xs text-gray-500">{{ $emailCauser }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Acción</p>
                        <span class="inline-block text-xs px-2 py-1 rounded-full {{ $this->claseAccion($detalle->event) }}">
                            {{ $this->etiquetaAccion($detalle->event) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Módulo</p>
                        <p class="text-sm text-gray-800">{{ $this->etiquetaModulo($detalle->log_name) }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Descripción</p>
                        <p class="text-sm text-gray-800">{{ $detalle->description }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Registro</p>
                        <p class="text-sm text-gray-800">
                            {{ $detalle->subject_type ? class_basename($detalle->subject_type) : '—' }}
                            @if ($detalle->subject_id)
                                <span class="text-xs text-gray-500">({{ $detalle->subject_id }})</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">IP / Navegador</p>
                        <p class="text-sm text-gray-800">{{ $detalle->getProperty('ip', '—') }}</p>
                        <p class="text-xs text-gray-500 break-all">{{ $detalle->getProperty('user_agent', '—') }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Cambios registrados</h3>

                    @if (count($claves) > 0)
                        <div class="overflow-x-auto border rounded-md">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Campo</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Valor anterior</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Valor nuevo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($claves as $clave)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-700 align-top">{{ $clave }}</td>
                                            <td class="px-3 py-2 text-gray-600 align-top whitespace-pre-wrap break-all">
                                                {{ $this->formatearValor($anteriores[$clave] ?? null) }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-600 align-top whitespace-pre-wrap break-all">
                                                {{ $this->formatearValor($nuevos[$clave] ?? null) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Sin cambios de atributos registrados.</p>
                    @endif
                </div>

                @if (count($propiedades) > 0)
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Contexto adicional</h3>
                        <div class="overflow-x-auto border rounded-md">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($propiedades as $clave => $valor)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-700 align-top">{{ $clave }}</td>
                                            <td class="px-3 py-2 text-gray-600 align-top whitespace-pre-wrap break-all">
                                                {{ $this->formatearValor($valor) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cerrarDetalle">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
