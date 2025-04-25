<div class="p-4 space-y-6">
    {{-- Sección TipoIndicador --}}
    <div class="border p-4 rounded shadow">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold">Tipos de Indicador</h2>

            <button type="submit" wire:click="createTipo" size="sm" style="font-size: 0.75rem; font-weight: 600"
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                + Nuevo Tipo
            </button>

        </div>
        <ul class="mt-4 divide-y divide-gray-200">
            @foreach ($tipo_indicadores as $tipo)
                <li class="flex justify-between items-center py-2">
                    <span class="font-medium text-gray-700">{{ $tipo->nombre }}</span>
                    <div class="space-x-2">
                        <a wire:click="editTipo({{ $tipo->tipo_indicador_id }})"
                            class="text-green-600 hover:text-green-800 cursor-pointer mx-2" title="Editar Tipo">
                            <i class="fa-regular fa-edit fa-xl  "></i>
                        </a>

                        <a wire:click="deleteTipo({{ $tipo->tipo_indicador_id }})"
                            class="text-red-600 hover:text-red-800 cursor-pointer mx-2" title="Eliminar">
                            <i class="fa-solid fa-trash fa-xl"></i>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Sección Indicador --}}
    <div class="border p-4 rounded shadow">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold">Indicadores</h2>


            <button type="submit" wire:click="createIndicador" size="sm"
                style="font-size: 0.75rem; font-weight: 600"
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                + Nuevo Indicador
            </button>

        </div>
        <table class="mt-4 w-full text-sm border-t border-gray-300">
            <thead>
                <tr class="text-left border-b border-gray-300">
                    <th class="py-1">Nombre</th>
                    <th class="py-1">Tipo</th>
                    <th class="py-1">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($indicadores as $ind)
                    <tr class="border-b border-gray-200">
                        <td class="py-1">{{ $ind->nombre }}</td>
                        <td class="py-1 text-gray-600">{{ $ind->tipoIndicador?->nombre }}</td>
                        <td class="py-1 space-x-2">
                            <a wire:click="editIndicador({{ $ind->indicador_id }})"
                                class="text-green-600 hover:text-green-800 cursor-pointer mx-2"
                                title="Editar Indicador">
                                <i class="fa-regular fa-edit fa-xl  "></i>
                            </a>

                            <a wire:click="deleteIndicador({{ $ind->indicador_id }})"
                                class="text-red-600 hover:text-red-800 cursor-pointer mx-2" title="Eliminar">
                                <i class="fa-solid fa-trash fa-xl"></i>
                            </a>
                        </td>


                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal TipoIndicador --}}
    <x-dialog-modal wire:model="showTipoModal">
        <x-slot name="title">{{ $isCreatingTipo ? 'Crear' : 'Editar' }} Tipo de Indicador</x-slot>
        <x-slot name="content">
            <input type="text" wire:model.defer="tipo_nombre" class="w-full border rounded p-1"
                placeholder="Nombre del tipo">
            @error('tipo_nombre')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showTipoModal', false)">
                Volver
            </x-secondary-button>

            <button type="button" wire:click="saveTipo" style="font-size: 0.75rem; font-weight: 600"
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                Guardar
            </button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal Indicador --}}
    <x-dialog-modal wire:model="showIndicadorModal">
        <x-slot name="title">{{ $isCreatingIndicador ? 'Crear' : 'Editar' }} Indicador</x-slot>
        <x-slot name="content">
            <div class="space-y-2">
                <input type="text" wire:model.defer="indicador_nombre" class="w-full border rounded p-1"
                    placeholder="Nombre del indicador">
                <select wire:model.defer="tipo_indicador_id" class="w-full border rounded p-1">
                    <option value="">Seleccionar tipo</option>
                    @foreach ($tipo_indicadores as $tipo)
                        <option value="{{ $tipo->tipo_indicador_id }}">{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @error('indicador_nombre')
                <span class="text-red-600 text-sm">{{ $message }}</span>
            @enderror
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showIndicadorModal', false)">
                Volver
            </x-secondary-button>

            <button type="button" wire:click="saveIndicador" style="font-size: 0.75rem; font-weight: 600"
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                Guardar
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
