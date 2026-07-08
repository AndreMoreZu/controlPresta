<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto'     => ['required', 'integer', 'min:1', 'max:99999999'],
            'frecuencia'=> ['required', 'in:mensual,quincenal,semanal'],
            'inicio'    => ['required', 'date', 'before_or_equal:today'],
            'proximo'   => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required'          => 'El monto es obligatorio.',
            'monto.integer'           => 'El monto debe ser un número entero.',
            'monto.min'               => 'El monto debe ser mayor a 0.',
            'frecuencia.required'     => 'Seleccioná una frecuencia.',
            'frecuencia.in'           => 'La frecuencia no es válida.',
            'inicio.required'         => 'La fecha de inicio es obligatoria.',
            'inicio.date'             => 'La fecha de inicio no es válida.',
            'inicio.before_or_equal'  => 'La fecha de inicio no puede ser futura.',
            'proximo.required'        => 'La fecha del próximo cobro es obligatoria.',
            'proximo.date'            => 'La fecha del próximo cobro no es válida.',
            'proximo.after_or_equal'  => 'La fecha del próximo cobro no puede ser anterior a hoy.',
        ];
    }
}
