<?php

namespace App\Rules;

use App\Support\NombreCertificado;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LargoNombreCertificado implements ValidationRule
{
    public function __construct(protected ?string $nombre = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (NombreCertificado::excede((string) $value, $this->nombre)) {
            $fail(NombreCertificado::mensaje((string) $value, $this->nombre));
        }
    }
}
