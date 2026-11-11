<?php

namespace App\Rules;

use App\Support\DocumentoIdentidad;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class DocumentoUnico implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column = 'dni_norm',
        private mixed $ignorarId = null,
        private string $ignorarColumna = 'id',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalizado = DocumentoIdentidad::normalizar(is_string($value) ? $value : (string) $value);

        if ($normalizado === '') {
            return;
        }

        $query = DB::table($this->table)->where($this->column, $normalizado);

        if ($this->ignorarId !== null) {
            $query->where($this->ignorarColumna, '!=', $this->ignorarId);
        }

        if ($query->exists()) {
            $fail('El número de documento ya está registrado.');
        }
    }
}
