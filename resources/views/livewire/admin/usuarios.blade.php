<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <h2 class="text-xl font-bold mb-4">Gestión de Usuarios</h2>
    <!-- Campo de búsqueda -->
    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Ingrese el nombre o email del Usuario">
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
                            <td class="px-6 py-2">{{ $usuario->roles->first()->name ?? 'Sin rol' }}</td>

                            <td class="px-6 py-2">{{ $usuario->email }}</td>
                            {{-- <td>{{ $usuario->roles->first()->name }}</td> --}}
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">

                                {{-- aqui esta el boton editar que dispara el metodo edit y este muestra el modal --}}
                                <a class="btn btn-blue-green px-2" href="#"
                                    wire:click="editar({{ $usuario->id }})">
                                    <i class="fas fa-edit"></i>
                                </a>
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
                {{-- Editando la Carrera: {{ $carreraEdit->nombre }} --}}
            </x-slot>


            <x-slot name="content">
                @if ($usuario_edit)
                    <h2>Rol del Usuario: {{ $usuario_edit->name }}</h2>
                @else
                    <h2>Rol del Usuario</h2>
                @endif

                <div>
                    <label>Nombre</label>
                    <input wire:model="name" type="text" class="w-full border p-2 rounded">
                    @error('name')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label>Email</label>
                    <input wire:model="email" type="email" class="w-full border p-2 rounded">
                    @error('email')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label>Nueva Contraseña (opcional)</label>
                    <input wire:model="password" type="password" class="w-full border p-2 rounded">
                    @error('password')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4 flex flex-wrap gap-6">
                    @foreach ($roles as $rol)
                        <label for="rol-{{ $rol->id }}"
                            class="flex items-center space-x-4 text-sm font-medium text-gray-700">

                            <input wire:model="rol_id_edit" type="radio" id="rol-{{ $rol->id }}"
                                value="{{ $rol->id }}">
                            <span>{{ $rol->name }}</span>
                        </label>
                    @endforeach
                    @error('rol_id_edit')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

            </x-slot>

            <x-slot name="footer">
                <x-secondary-button class="mr-2" wire:click="$set('open_edit', false)">
                    Cancelar
                </x-secondary-button>

                <x-button wire:loading.attr="disabled" class="disabled:opacity-25">
                    Actualizar
                </x-button>
            </x-slot>

        </x-dialog-modal>

    </form>
    {{-- //-----------------------------------------------------------------
    @if ($editando)
        <div class="bg-white p-6 border rounded shadow">
            <h3 class="text-xl font-semibold mb-4">Editar Usuario</h3>

            <form wire:submit.prevent="actualizar" class="space-y-4">
               

                <div>
                    <label>Rol</label>
                    @foreach ($roles as $r)
                        <div>
                            <label>
                                <input type="radio" wire:model="role" value="{{ $r }}">
                                {{ ucfirst($r) }}
                            </label>
                        </div>
                    @endforeach
                    @error('role')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </div>

            </form>
        </div>
    @endif --}}

</div>
