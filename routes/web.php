<?php

use App\Http\Controllers\QRController;
use App\Http\Controllers\WelcomeController;
use App\Livewire\Asistencias;
use App\Livewire\CrearEvento;
use App\Livewire\Eventos;
use App\Livewire\HabilitarPlanilla;
use App\Livewire\Indicadores;
use App\Livewire\Participantes;
use App\Livewire\ProcesarAprobaciones;
use App\Livewire\RegistroEventoPublico;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/inscripcion/{tipoEvento}/{eventoId}', RegistroEventoPublico::class)->name('inscripcion.evento');

// Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {
//     //Route::get('/registrar_evento', CrearEvento::class)->name('registrar_evento');
//     Route::get('/registrar_evento/{evento_id?}', CrearEvento::class)->name('registrar_evento');
//     Route::get('/eventos/{tab?}', Eventos::class)->name('eventos');
//     Route::get('/eventos/{evento_id}/habilitar', HabilitarPlanilla::class)->name('habilitar_planilla');
//     Route::get('/planilla/{evento_id}/editar', HabilitarPlanilla::class)->name('editar_planilla');

//     Route::get('/participantes', Participantes::class)->name('participantes');
//     Route::get('/asistencias', Asistencias::class)->name('asistencias');
//     Route::get('/indicadores', Indicadores::class)->name('indicadores');
//     Route::get('/validar-participante/{evento_id}/{participante_id}', [QRController::class, 'show'])->name('validar.participante');
//     Route::get('/procesar-aprobaciones', ProcesarAprobaciones::class)->name('procesar_aprobaciones');
// });

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    Route::middleware('can:crear_eventos')->group(function () {
        Route::get('/registrar_evento/{evento_id?}', CrearEvento::class)->name('registrar_evento');
        Route::get('/eventos/{tab?}', Eventos::class)->name('eventos');
        Route::get('/eventos/{evento_id}/habilitar', HabilitarPlanilla::class)->name('habilitar_planilla');
        Route::get('/planilla/{evento_id}/editar', HabilitarPlanilla::class)->name('editar_planilla');
        Route::get('/participantes', Participantes::class)->name('participantes');
        Route::get('/indicadores', Indicadores::class)->name('indicadores');
    });

    Route::middleware('can:procesar_aprobaciones')->group(function () {
        Route::get('/procesar-aprobaciones', ProcesarAprobaciones::class)->name('procesar_aprobaciones');
    });

    Route::middleware('can:asistencias')->group(function () {
        Route::get('/asistencias', Asistencias::class)->name('asistencias');
    });

    Route::get('/validar-participante/{evento_id}/{participante_id}', [QRController::class, 'show'])->name('validar.participante');
});
