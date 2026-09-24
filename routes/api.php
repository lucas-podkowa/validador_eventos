<?php

use App\Http\Controllers\Api\V1\CertificadoExternoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware(['auth:sanctum', 'api.cliente.activo'])->group(function () {
    Route::post('/certificados', [CertificadoExternoController::class, 'store'])
        ->middleware(['ability:certificados:emitir', 'throttle:api-certificados'])
        ->name('api.certificados.store');

    Route::get('/certificados/{certificado}', [CertificadoExternoController::class, 'show'])
        ->middleware('ability:certificados:leer')
        ->name('api.certificados.show');
});
