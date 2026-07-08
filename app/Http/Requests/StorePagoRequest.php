<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el formulario de registro de pago con soporte de pagos parciales (§5.9).
 *
 * Cada concepto (interés, multa, intereses atrasados, abono) es nullable:
 * puede no estar presente (ej. no hay multa = el campo no se muestra).
 * Los montos los clampea el service al máximo adeudado; aquí solo validamos tipo.
 */
class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metodo'             => ['required', 'in:efectivo,sinpe,transferencia'],
            'pago_interes'       => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'pago_multa'         => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'pago_intereses_atr' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'abono'              => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'proximo_cobro'      => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addYears(2)->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'metodo.required'           => 'Seleccioná un método de pago.',
            'metodo.in'                 => 'El método de pago no es válido.',
            'pago_interes.integer'      => 'El monto del interés debe ser un número entero.',
            'pago_interes.min'          => 'El monto del interés no puede ser negativo.',
            'pago_multa.integer'        => 'El monto de la multa debe ser un número entero.',
            'pago_multa.min'            => 'El monto de la multa no puede ser negativo.',
            'pago_intereses_atr.integer' => 'El monto de intereses atrasados debe ser un número entero.',
            'pago_intereses_atr.min'    => 'El monto de intereses atrasados no puede ser negativo.',
            'abono.integer'             => 'El abono debe ser un número entero.',
            'abono.min'                 => 'El abono no puede ser negativo.',
            'proximo_cobro.date'         => 'La fecha del próximo cobro no es válida.',
            'proximo_cobro.after_or_equal'  => 'La fecha del próximo cobro no puede ser anterior a hoy.',
            'proximo_cobro.before_or_equal' => 'La fecha del próximo cobro no puede ser más de 2 años en el futuro.',
        ];
    }
}
