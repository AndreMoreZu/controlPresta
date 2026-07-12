<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    private const MAX_MONTO = 999999999;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'trabajo' => ['nullable', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:50', 'unique:clientes,cedula'],
            'cedula_foto_frente' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cedula_foto_atras' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'tiene_prestamo' => ['nullable', 'boolean'],
            'monto' => ['required_if:tiene_prestamo,1', 'nullable', 'integer', 'min:1', 'max:'.self::MAX_MONTO],
            'saldo' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_MONTO],
            'frecuencia' => ['required_if:tiene_prestamo,1', 'nullable', 'in:mensual,quincenal,semanal'],
            'interes_pagados' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_MONTO],
            'inicio' => ['required_if:tiene_prestamo,1', 'nullable', 'date'],
            'proximo' => ['required_if:tiene_prestamo,1', 'nullable', 'date'],

            'tiene_atraso' => ['nullable', 'boolean'],
            'atraso_desde' => ['nullable', 'date', 'before_or_equal:today'],
            'multa_acumulada' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_MONTO],
            'intereses_atrasados' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_MONTO],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'cedula.unique' => 'Ya existe un cliente registrado con esa cédula.',
            'cedula_foto_frente.image' => 'La foto del frente de la cédula debe ser una imagen.',
            'cedula_foto_frente.max' => 'La foto del frente de la cédula no puede pesar más de 4 MB.',
            'cedula_foto_atras.image' => 'La foto del reverso de la cédula debe ser una imagen.',
            'cedula_foto_atras.max' => 'La foto del reverso de la cédula no puede pesar más de 4 MB.',
            'monto.required_if' => 'Ingresá el monto original del préstamo.',
            'monto.max' => 'El monto es demasiado alto.',
            'saldo.max' => 'El saldo es demasiado alto.',
            'interes_pagados.max' => 'El monto es demasiado alto.',
            'multa_acumulada.max' => 'El monto es demasiado alto.',
            'intereses_atrasados.max' => 'El monto es demasiado alto.',
            'atraso_desde.before_or_equal' => 'La fecha no puede ser futura.',
            'frecuencia.required_if' => 'Elegí la frecuencia de pago.',
            'inicio.required_if' => 'Ingresá la fecha en que se le prestó.',
            'proximo.required_if' => 'Ingresá la fecha del próximo cobro.',
        ];
    }
}
