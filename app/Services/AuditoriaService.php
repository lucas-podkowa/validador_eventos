<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    public function registrar(
        string $logName,
        string $descripcion,
        ?Model $subject = null,
        array $properties = [],
        ?string $event = null,
    ): void {
        $logger = activity()->useLog($logName);

        if (auth()->check()) {
            $logger->causedBy(auth()->user());
        }

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($event) {
            $logger->event($event);
        }

        if ($properties !== []) {
            $logger->withProperties($properties);
        }

        $logger->log($descripcion);
    }
}
