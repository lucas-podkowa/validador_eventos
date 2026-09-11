<div class="px-4 sm:px-6 lg:px-8 py-4">
    <h2 class="text-xl font-bold mb-4">Gestión de Usuarios</h2>
    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="md:col-span-2">
            <input type="text" wire:model.live="search"
                class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
                placeholder="Ingrese el nombre o email del Usuario">
        </div>
        <div>
            <select wire:model.live="selectedRole"
                class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
                <option value="Todos">Todos</option>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->name }}">{{ $rol->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($usuarios->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="bg-gray-200">
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Nombre</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Rol</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Email</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td class="px-6 py-2">{{ $usuario->name }}</td>
                            <td class="px-6 py-2">{{ $usuario->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                            <td class="px-6 py-2">{{ $usuario->email }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">

                                {{-- aqui esta el boton editar que dispara el metodo edit y este muestra el modal --}}
                                <button class="btn-action-edit" title="Editar" wire:click="editar({{ $usuario->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    @else
            <div>No se encontraron registros</div>

        @endif

        <div class="py-4">
            @if ($usuarios->links())
                {{ $usuarios->links() }}
            @endif
        </div>
    </x-table>

    <form wire:submit.prevent="actualizar">
        <x-dialog-modal wire:model="open_edit">

            <x-slot name="title">
                @if ($usuario_edit)
                    <h3>Editando el Usuario: {{ $usuario_edit->name }}</h3>
                @else
                    <h3>Editando el Usuario</h3>
                @endif
            </x-slot>


            <x-slot name="content">
                <div>
                    <label>Nombre</label>
                    <input wire:model="name" type="text" class="w-full border p-2 rounded">
                    @error('name')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4">
                    <label>Email</label>
                    <input wire:model="email" type="email" class="w-full border p-2 rounded">
                    @error('email')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4">
                    <label>DNI</label>
                    <input wire:model="dni" type="text" inputmode="numeric" class="w-full border p-2 rounded"
                        placeholder="Solo números">
                    @error('dni')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4">
                    <label>Nueva Contraseña (opcional)</label>
                    <input wire:model="password" type="password" autocomplete="new-password"
                        class="w-full border p-2 rounded">
                    @error('password')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4 flex flex-wrap gap-6">
                    @foreach ($roles as $rol)
                        <label for="rol-{{ $rol->id }}"
                            class="flex items-center space-x-2 text-sm font-medium text-gray-700">
                            <input wire:model="roles_selected" type="checkbox" id="rol-{{ $rol->id }}"
                                value="{{ $rol->id }}">
                            <span>{{ $rol->name }}</span>
                        </label>
                    @endforeach
                    @error('roles_selected')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 border-t pt-4">
                    <label class="mb-2 block font-semibold text-gray-700">Participante vinculado</label>

                    @if ($participante_vinculado)
                        <div class="flex items-center justify-between rounded border bg-gray-50 p-3">
                            <div class="text-sm">
                                <p class="font-medium text-gray-800">
                                    {{ $participante_vinculado['apellido'] }}, {{ $participante_vinculado['nombre'] }}
                                </p>
                                <p class="text-gray-500">
                                    DNI {{ $participante_vinculado['dni'] }} &middot; {{ $participante_vinculado['mail'] }}
                                </p>
                            </div>
                            <button type="button" wire:click="desvincularParticipante"
                                class="text-sm font-medium text-red-600 underline hover:text-red-800">
                                Desvincular
                            </button>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <input wire:model="busqueda_participante" type="text"
                                class="w-full rounded border p-2"
                                placeholder="Buscar por DNI, correo, nombre o apellido">
                            <button type="button" wire:click="buscarParticipante"
                                class="rounded border px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                Buscar
                            </button>
                        </div>
                        @error('busqueda_participante')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror

                        @if ($participante_candidato)
                            <div class="mt-3 flex items-center justify-between rounded border bg-white p-3">
                                <div class="text-sm">
                                    <p class="font-medium text-gray-800">
                                        {{ $participante_candidato['apellido'] }}, {{ $participante_candidato['nombre'] }}
                                    </p>
                                    <p class="text-gray-500">
                                        DNI {{ $participante_candidato['dni'] }} &middot; {{ $participante_candidato['mail'] }}
                                    </p>
                                </div>
                                <button type="button" wire:click="vincularParticipante"
                                    class="rounded bg-blue-600 px-3 py-1 text-sm font-medium text-white hover:bg-blue-700">
                                    Vincular
                                </button>
                            </div>
                        @endif

                        <p class="mt-2 text-xs text-gray-400">
                            Solo se muestran participantes que todavía no tienen una cuenta vinculada.
                        </p>
                    @endif
                </div>
            </x-slot>

            <x-slot name="footer">
                <div class="flex">
                    <x-secondary-button class="mr-2" wire:click="$set('open_edit', false)">
                        Cancelar
                    </x-secondary-button>

                    <x-button wire:loading.attr="disabled" style="font-size: 0.75rem; font-weight: 600"
                        class="disabled:opacity-25 btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                        Actualizar
                    </x-button>
                </div>
            </x-slot>

        </x-dialog-modal>

    </form>
</div>