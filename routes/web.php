<?php

use App\Http\Controllers\QRController;
use App\Http\Controllers\WelcomeController;
use App\Livewire\CrearEvento;
use App\Livewire\Eventos;
use App\Livewire\RegistroEventoPublico;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');


Route::get('/inscripcion/{tipoEvento}/{eventoId}', RegistroEventoPublico::class)->name('inscripcion.evento');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {
    //Route::get('/registrar_evento', CrearEvento::class)->name('registrar_evento');
    Route::get('/registrar_evento/{evento_id?}', CrearEvento::class)->name('registrar_evento');
    Route::get('/eventos', Eventos::class)->name('eventos');
    Route::get('/validar-participante/{evento_id}/{participante_id}', [QRController::class, 'show'])->name('validar.participante');
});
