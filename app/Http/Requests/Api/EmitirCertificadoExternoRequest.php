<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EmitirCertificadoExternoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_ref' => ['required', 'string', 'max:191'],
            'tipo' => ['required', 'string', 'max:50'],
            'plantilla_codigo' => ['nullable', 'string', 'max:191'],
            'contexto_id' => ['required', 'integer', 'exists:contexto,contexto_id'],
            'fecha_emision' => ['nullable', 'date'],

            'tutor' => ['required', 'array'],
            'tutor.apellido' => ['required', 'string', 'max:100'],
            'tutor.nombres' => ['required', 'string', 'max:100'],
            'tutor.dni' => ['required', 'digits_between:6,12'],
            'tutor.email' => ['required', 'email', 'max:191'],
            'tutor.telefono' => ['required', 'string', 'max:30'],
            'tutor.cargo' => ['nullable', 'string', 'max:100'],

            'practica' => ['required', 'array'],
            'practica.carrera' => ['nullable', 'string', 'max:191'],
            'practica.estudiante_apellido_nombres' => ['nullable', 'string', 'max:191'],
            'practica.estudiante_dni' => ['nullable', 'digits_between:6,12'],
            'practica.institucion' => ['nullable', 'string', 'max:191'],
            'practica.periodo_inicio' => ['nullable', 'date'],
            'practica.periodo_fin' => ['nullable', 'date', 'after_or_equal:practica.periodo_inicio'],
            'practica.horas' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'practica.resolucion' => ['nullable', 'string', 'max:191'],
        ];
    }
}
