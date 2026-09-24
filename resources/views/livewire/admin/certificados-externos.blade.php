<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Certificados externos</h2>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <input type="text" wire:model.live="search"
            class="w-full max-w-md px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar por receptor, DNI o referencia externa...">

        <label class="inline-flex items-center text-sm">
            <input type="checkbox" wire:model.live="solo_revision" class="mr-2">
            <span>Solo pendientes de revisión</span>
        </label>
    </div>

    @if ($certificados->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Receptor</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Referencia</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Match</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Cuenta</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Fecha</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($certificados as $certificado)
                        <tr>
                            <td class="px-4 py-2">
                                <p class="font-medium">{{ $certificado->receptor_nombre }}</p>
                                <p class="text-xs text-gray-500">DNI {{ $certificado->receptor_dni }}</p>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $certificado->external_ref }}</td>
                            <td class="px-4 py-2 text-center">
                                @if ($certificado->match_estado === \App\Models\CertificadoExterno::MATCH_REVISAR)
                                    <span class="inline-block bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded-full">Revisar</span>
                                @else
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Auto</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if ($certificado->participante && $certificado->participante->user_id)
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Vinculada</span>
                                @else
                                    <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">Sin cuenta</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-gray-500">
                                {{ $certificado->created_at?->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                @if ($certificado->certificado_path)
                                    <a href="{{ route('mis_certificados.externo', $certificado) }}" target="_blank"
                                        class="btn-action-edit" title="Descargar">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endif
                                <button wire:click="abrirVincular({{ $certificado->certificado_externo_id }})"
                                    class="btn-action-edit" title="Vincular a cuenta">
                                    <i class="fas fa-link"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $certificados->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron certificados externos.</div>
    @endif

    <form wire:submit.prevent="vincular">
        <x-dialog-modal wire:model="vincular_modal">
            <x-slot name="title">Vincular certificado a una cuenta</x-slot>

            <x-slot name="content">
                <div class="space-y-4">
                    <p class="text-sm text-gray-500">
                        Ingresá el correo de la cuenta de usuario que debe ver este certificado.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo de la cuenta</label>
                        <input wire:model="vincular_email" type="email"
                            class="w-full border-gray-300 rounded-md shadow-sm sm:text-sm"
                            placeholder="usuario@fio.unam.edu.ar">
                        @error('vincular_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-secondary-button wire:click="$set('vincular_modal', false)">Cancelar</x-secondary-button>
                    <x-button type="submit">Vincular</x-button>
                </div>
            </x-slot>
        </x-dialog-modal>
    </form>
</div>
