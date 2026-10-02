<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Clientes de API</h2>
        <button wire:click="abrirCrear"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 text-xs font-semibold">
            + Nuevo Cliente
        </button>
    </div>

    <p class="mb-4 text-sm text-gray-500">
        Cada cliente (por ejemplo, PPS) usa tokens Sanctum para emitir certificados externos.
    </p>

    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar por nombre...">
    </div>

    @if ($clientes->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Cliente</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Descripción</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Emisiones</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Estado</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($clientes as $cliente)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $cliente->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $cliente->descripcion ?? '—' }}</td>
                            <td class="px-4 py-2 text-center text-sm">{{ $cliente->emisiones_count }}</td>
                            <td class="px-4 py-2 text-center">
                                @if ($cliente->activo)
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Activo</span>
                                @else
                                    <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="abrirTokens({{ $cliente->api_cliente_id }})"
                                    class="btn-action-edit" title="Tokens">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button wire:click="editar({{ $cliente->api_cliente_id }})"
                                    class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="alternarActivo({{ $cliente->api_cliente_id }})"
                                    class="btn-action-edit" title="{{ $cliente->activo ? 'Deshabilitar' : 'Habilitar' }}">
                                    <i class="fas {{ $cliente->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <button wire:click="eliminar({{ $cliente->api_cliente_id }})"
                                    wire:confirm="¿Eliminar el cliente '{{ $cliente->nombre }}' y sus tokens?"
                                    class="btn-action-delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $clientes->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron clientes de API.</div>
    @endif

    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Cliente de API' : 'Nuevo Cliente de API' }}
            </x-slot>

            <x-slot name="content">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input wire:model="nombre" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: PPS - Prácticas Profesionales Supervisadas">
                        @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                        <input wire:model="descripcion" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('descripcion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center text-sm">
                        <input type="checkbox" wire:model="activo" class="mr-2">
                        <span>Cliente activo</span>
                    </label>
                </div>
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-secondary-button wire:click="$set('open_modal', false)">Cancelar</x-secondary-button>
                    <x-button type="submit">Guardar</x-button>
                </div>
            </x-slot>
        </x-dialog-modal>
    </form>

    <x-dialog-modal wire:model="token_modal">
        <x-slot name="title">Tokens del cliente</x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="mb-3 font-semibold text-gray-700">Generar nuevo token</h4>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre (opcional)</label>
                            <input wire:model="token_name" type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm sm:text-sm"
                                placeholder="Ej: produccion">
                            @error('token_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Habilidades</span>
                            @foreach (\App\Livewire\Admin\ApiClientes::ABILITIES as $ability)
                                <label class="mr-4 inline-flex items-center text-sm">
                                    <input type="checkbox" value="{{ $ability }}" wire:model="abilities" class="mr-2">
                                    <span>{{ $ability }}</span>
                                </label>
                            @endforeach
                            @error('abilities') <span class="block text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <x-button wire:click="generarToken" type="button">Generar token</x-button>
                    </div>

                    @if ($nuevo_token)
                        <div class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-3">
                            <p class="text-sm font-semibold text-amber-800">Copiá el token ahora: no se volverá a mostrar.</p>
                            <code class="mt-2 block break-all rounded bg-white p-2 text-xs">{{ $nuevo_token }}</code>
                        </div>
                    @endif
                </div>

                <div>
                    <h4 class="mb-2 font-semibold text-gray-700">Tokens existentes</h4>

                    @if ($tokens->isEmpty())
                        <p class="text-sm text-gray-500">No hay tokens generados.</p>
                    @else
                        <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                            @foreach ($tokens as $token)
                                <li class="flex items-center justify-between px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ $token->name }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ implode(', ', $token->abilities ?? []) }}
                                            &middot; último uso:
                                            {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : 'nunca' }}
                                        </p>
                                    </div>
                                    <button wire:click="revocarToken({{ $token->id }})"
                                        wire:confirm="¿Revocar el token '{{ $token->name }}'?"
                                        class="btn-action-delete ms-3" title="Revocar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('token_modal', false)">Cerrar</x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
