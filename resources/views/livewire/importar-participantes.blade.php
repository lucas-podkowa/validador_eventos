<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="max-w-3xl mx-auto mt-8 p-6 bg-white shadow rounded-2xl">
        <h2 class="text-2xl font-bold mb-4 text-gray-700">Importar Participantes al Evento</h2>

        <h3 class="text-gray-600 mb-4">
            {{ $evento->nombre }}<br>
        </h3>

        {{-- <form wire:submit.prevent="importar" class="space-y-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Archivo (.xlsx o .csv)</label>
                <input type="file" wire:model="archivo" class="border rounded w-full p-2">
                @error('archivo')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow">
                    Importar
                </button>
            </div>
        </form> --}}

        <div class="w-full mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 w-full">
                <div class="flex items-center mb-3">
                    <i class="fa-solid fa-circle-info text-blue-600 mr-2"></i>
                    <span class="font-semibold text-blue-700">Formato esperado del archivo</span>
                </div>
                <p class="mb-4 text-gray-700 text-sm">
                    El archivo debe ser <b>.xlsx</b> o <b>.csv</b> y la primera fila debe contener exactamente las
                    siguientes columnas (los datos previsualizados a continuación son solo un ejemplo):
                </p>

                <!-- Simulación de cabecera del archivo -->
                <div class="bg-white border-2 border-blue-300 rounded-lg overflow-x-auto mb-4 shadow-sm">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-blue-600">
                                <th class="text-white font-bold text-sm p-3 border-r border-blue-400 whitespace-nowrap">
                                    DNI</th>
                                <th class="text-white font-bold text-sm p-3 border-r border-blue-400 whitespace-nowrap">
                                    APELLIDO</th>
                                <th class="text-white font-bold text-sm p-3 border-r border-blue-400 whitespace-nowrap">
                                    NOMBRE</th>
                                <th class="text-white font-bold text-sm p-3 border-r border-blue-400 whitespace-nowrap">
                                    MAIL</th>
                                <th class="text-white font-bold text-sm p-3 whitespace-nowrap">TELÉFONO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-gray-50">
                                <td class="text-gray-600 text-sm p-3 border-r border-gray-200 text-center">12345678</td>
                                <td class="text-gray-600 text-sm p-3 border-r border-gray-200 text-center">Perez</td>
                                <td class="text-gray-600 text-sm p-3 border-r border-gray-200 text-center">Juan</td>
                                <td class="text-gray-600 text-sm p-3 border-r border-gray-200 text-center">juan@mail.com
                                </td>
                                <td class="text-gray-600 text-sm p-3 text-center">3755998877</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- <div class="flex items-center gap-2 text-sm text-gray-700 mb-4">
                    <i class="fa-solid fa-download text-green-600"></i>
                    <span>Puedes descargar una plantilla y completarla para evitar errores de formato.</span>
                </div> --}}

                <button type="button" wire:click="descargarPlantilla" style="background-color: #16a34a;"
                    class="w-full px-6 py-3 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-3 hover:bg-green-700 transition shadow-md hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 384 512">
                        <path
                            d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM155.7 250.2L192 286.5l36.3-36.3c6.2-6.2 16.4-6.2 22.6 0s6.2 16.4 0 22.6l-48 48c-6.2 6.2-16.4 6.2-22.6 0l-48-48c-6.2-6.2-6.2-16.4 0-22.6s16.4-6.2 22.6 0zM192 368c8.8 0 16-7.2 16-16V272c0-8.8-7.2-16-16-16s-16 7.2-16 16v80c0 8.8 7.2 16 16 16z" />
                    </svg>
                    <span class="text-base">Descargar una plantilla y completarla para evitar errores de formato</span>
                    <i class="fa-solid fa-download text-lg"></i>
                </button>
            </div>
        </div>

        <form wire:submit.prevent="importar" class="space-y-6">
            <div class="flex flex-col items-center justify-center w-full">

                <label for="archivo"
                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition relative">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500" wire:loading.remove
                        wire:target="archivo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16V4m0 0l-3 3m3-3l3 3M17 8v12m0 0l3-3m-3 3l-3-3" />
                        </svg>
                        <p class="text-sm"><span class="font-semibold">Haz clic o arrastra tu archivo aquí</span></p>
                        <p class="text-xs text-gray-400 mt-1">.xlsx o .csv</p>
                    </div>

                    <!-- Indicador de carga -->
                    <div wire:loading wire:target="archivo"
                        class="absolute inset-0 flex items-center justify-center bg-white/70 rounded-xl">
                        <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>

                    <input id="archivo" type="file" wire:model="archivo" class="hidden" />
                </label>

                <!-- Nombre del archivo cargado -->
                @if ($archivo)
                    <div
                        class="mt-3 flex items-center gap-2 text-sm text-gray-700 bg-gray-100 px-3 py-2 rounded-lg border">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Archivo seleccionado:</span>
                        <span
                            class="font-medium text-gray-900 truncate max-w-[200px]">{{ $archivo->getClientOriginalName() }}</span>
                    </div>
                @endif

                @error('archivo')
                    <span class="text-red-600 text-sm mt-2">{{ $message }}</span>
                @enderror
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg shadow transition">
                        Importar
                    </button>
                </div>
        </form>



        @if ($total > 0)
            <div class="mt-6 border-t pt-4">
                <h3 class="text-lg font-semibold mb-2 text-gray-700">Resultados:</h3>
                <p class="text-sm text-gray-600 mb-2">
                    Total: {{ $total }} | Exitosos: {{ $exitosos }} | Errores: {{ $errores }}
                </p>

                <table class="min-w-full text-sm border border-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">DNI</th>
                            <th class="p-2 border">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultados as $res)
                            <tr class="border-t">
                                <td class="p-2 border">{{ $res['dni'] }}</td>
                                <td class="p-2 border">{{ $res['estado'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
