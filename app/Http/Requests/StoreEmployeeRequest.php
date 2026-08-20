<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strtotime($value) <= strtotime($this->input('work_start'))) {
                        $fail('La hora de fin de jornada debe ser posterior a la de inicio.');
                    }
                },
            ],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ];
    }

    /**
     * Get custom validation messages in Spanish.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del empleado es obligatorio.',
            'name.max' => 'El nombre del empleado no puede superar los 100 caracteres.',
            'email.email' => 'El correo no tiene un formato válido.',
            'phone.max' => 'El teléfono no puede superar los 20 caracteres.',
            'work_start.required' => 'La hora de inicio de jornada es obligatoria.',
            'work_start.date_format' => 'La hora de inicio de jornada debe usar el formato HH:MM.',
            'work_end.required' => 'La hora de fin de jornada es obligatoria.',
            'work_end.date_format' => 'La hora de fin de jornada debe usar el formato HH:MM.',
            'services.array' => 'Los servicios deben enviarse como una lista.',
            'services.*.exists' => 'Uno de los servicios seleccionados no existe.',
        ];
    }
}
