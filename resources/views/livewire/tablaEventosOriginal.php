<x-table>
    {{-- ------------------- Filtros de la tabla ------------------------------------------------------- --}}
    <div class="px-6 py-4 flex">
        <!-- input de jetstream utilizado para el buscador -->
        <x-input class="flex-1 mr-4" wire:model.live="search" placeholder="Nombre del Evento" type="text" />

        <select wire:model.live="search_tipo_evento"
            class="flex-1 mr-4 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Todos los Tipos</option>
            @foreach ($tipos_eventos as $tipo)
            <option value="{{ $tipo->tipo_evento_id }}">
                {{ $tipo->nombre }}
            </option>
            @endforeach
        </select>

        {{-- este es un componente hijo, tiene un boton y el formulario para crear una carrera --}}


    </div>
    {{-- ------------------- Filtros de la tabla ---------------------------------------------- --}}


    @if ($eventos->count())

    <table class="w-full min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500"
                    wire:click="order('nombre')">
                    Nombre

                    @if ($sort == 'nombre')
                    @if ($direction == 'asc')
                    <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                    @else
                    <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                    @endif
                    @else
                    <i class="fas fa-sort float-right mt-1"></i>
                    @endif

                </th>
                <th scope="col" class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500"
                    wire:click="order('tipo_evento')">
                    Tipo de Evento

                    @if ($sort == 'tipo_evento')
                    @if ($direction == 'asc')
                    <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                    @else
                    <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                    @endif
                    @else
                    <i class="fas fa-sort float-right mt-1"></i>
                    @endif

                </th>

                <th scope="col" class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500"
                    wire:click="order('fecha_inicio')">
                    Fecha de Inicio

                    {{-- $sort --}}
                    @if ($sort == 'fecha_inicio')
                    @if ($direction == 'asc')
                    <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                    @else
                    <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                    @endif
                    @else
                    <i class="fas fa-sort float-right mt-1"></i>
                    @endif

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                    Acciones
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($eventos as $evento)
            <tr>
                <td class="px-6 py-3">
                    <div class="text-sm text-gray-900">
                        {{ $evento->nombre }}
                    </div>
                </td>
                <td class="px-6 py-3">
                    <div class="text-sm text-gray-900">
                        {{ $evento->tipoEvento->nombre }}
                    </div>
                </td>
                <td class="px-6 py-3">
                    <div class="text-sm text-gray-900">
                        {{ $evento->fecha_inicio }}
                    </div>
                </td>


                <td class="px-6 py-3 whitespace-nowrap text-sm font-medium">
                    <a class="btn btn-outline-primary" wire:click="detail({{ $evento->evento_id }})">
                        Detalle
                    </a>

                    <a class="btn btn-outline-primary"
                        wire:click="show_dialog_planilla({{ $evento->evento_id }})">
                        Habilitar Inscripción
                    </a>

                </td>
            </tr>
            @endforeach

        </tbody>
    </table>
    {{-- <div class="py-4">
                    @if ($eventos->links())
                        {{ $eventos->links() }}
    @endif

    </div> --}}
    @else
    <div class="px-6 py-4">
        No existen registros para mostrar
    </div>
    @endif

</x-table>

//--------------------



{{-- ------------------------  DIALOG MODAL visualizado al precionar el boton detail (eye) --------------------------- --}}

<x-dialog-modal wire:model="open_detail">

    <x-slot name="title" class="bg-gray-900">
        @if ($evento_selected)
        Participantes relacionados con: {{ $evento_selected->nombre }}
        @endif
    </x-slot>

    <x-slot name="content">

        <x-table>

            {{-- $carreras es esta en el metodo render de la clase y es enviada aqui como un parametro --}}
            @if (count($participantes) > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">

                    <tr>
                        <th scope="col"
                            class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500">
                            Nombre y Apellido
                        </th>
                        <th scope="col"
                            class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500">
                            DNI
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                            Correo Electrónico
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                            QR
                        </th>
                    </tr>


                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($participantes as $p)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                {{ $p->ape_nom }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                {{ $p->dni }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                {{ $p->mail }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                <img src="data:image/svg+xml;utf8,{{ rawurlencode($p->pivot->qrcode ?? '') }}"
                                    width="200" height="200" alt="QR Code" />
                            </div>
                        </td>
                        {{-- <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900">
                                        @if ($p->pivot->qrcode)
                                            <div class="qr-container">
                                                {!! $p->pivot->qrcode !!}
                                            </div>
                                        @else
                                            No disponible
                                        @endif
                                    </div>
                                </td> --}}

                    </tr>
                    @endforeach

                </tbody>
            </table>
            @else
            <div class="px-6 py-4">
                No existen registros para mostrar
            </div>
            @endif

        </x-table>

    </x-slot>


    <x-slot name="footer">
        <x-secondary-button class="mr-2" wire:click="$set('open_detail', false)">
            Volver
        </x-secondary-button>
    </x-slot>

</x-dialog-modal>

{{-- ------------------------  DIALOG MODAL habilitar_planilla--------------------------- --}}

<x-dialog-modal wire:model="open_planilla">

    <x-slot name="title" class="bg-gray-900">
        @if ($evento_selected)
        Habilitar Inscripciones al Evento: {{ $evento_selected->nombre }}
        @endif
    </x-slot>

    <x-slot name="content">
        <form wire:submit="habilitar_planilla" class="space-y-4">

            <div class="flex pt-4 px-6 gap-4">
                <!-- Fecha del evento -->
                <div class="w-1/2">
                    <label for="apertura" class="block text-sm font-medium text-gray-700">Apertura</label>
                    <input type="date" id="apertura" wire:model.live="apertura"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="w-1/2">
                    <label for="cierre" class="block text-sm font-medium text-gray-700">Cierre</label>
                    <input type="date" id="cierre" wire:model.live="cierre"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </form>

    </x-slot>


    <x-slot name="footer">

        <x-secondary-button wire:click="$set('open_planilla', false)">
            Volver
        </x-secondary-button>

        <x-button wire:loading.attr="disabled"
            class="gap-4 py-2 px-4 mx-4 border border-transparent shadow-sm text-md font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <!-- Mostrar un mensaje de procesamiento mientras se ejecuta -->
            <span wire:loading.remove>Habilitar</span>
            <span wire:loading>Procesando...</span>
        </x-button>


    </x-slot>

</x-dialog-modal>

<style>
    .qr-container {
        width: 200px;
        height: 200px;
    }
</style>