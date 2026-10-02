<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Estrategia de emisión masiva
    |--------------------------------------------------------------------------
    |
    | `usar_cola` define si las emisiones masivas se despachan a la cola (requiere
    | un worker corriendo: `queue:work`). Si es `false`, la emisión masiva se
    | resuelve de forma síncrona, en lotes auto-encadenados desde la interfaz.
    |
    | `umbral_sincronico`: cantidad de pendientes hasta la cual se emite todo en
    | un único request. Por encima, se procesa de a `tamano_lote`.
    |
    */

    'usar_cola' => env('EMISION_MASIVA_USAR_COLA', false),

    'umbral_sincronico' => (int) env('EMISION_MASIVA_UMBRAL', 25),

    'tamano_lote' => (int) env('EMISION_MASIVA_LOTE', 25),

];
