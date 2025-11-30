<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'specialist_id' => ['required', 'integer', 'exists:specialists,id'],
            'start_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Appointments can only be booked for today or future dates.',
        ];
    }
}
