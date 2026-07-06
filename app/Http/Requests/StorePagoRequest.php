<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metodo' => ['required', 'in:efectivo,sinpe,transferencia'],
            'abono'  => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'metodo.required' => 'Seleccioná un método de pago.',
            'metodo.in'       => 'El método de pago no es válido.',
            'abono.integer'   => 'El abono debe ser un número entero.',
            'abono.min'       => 'El abono no puede ser negativo.',
        ];
    }
}
