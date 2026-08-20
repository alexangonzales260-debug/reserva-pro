<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'start_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'service_id.required' => 'El servicio es obligatorio.',
            'service_id.exists' => 'El servicio seleccionado no existe.',
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'start_at.required' => 'La fecha y hora de inicio son obligatorias.',
            'start_at.date' => 'La fecha y hora de inicio no tienen un formato válido.',
            'start_at.after' => 'La fecha y hora de inicio deben ser posteriores al momento actual.',
            'notes.max' => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }
}
