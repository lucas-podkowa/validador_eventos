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
            </div>

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
