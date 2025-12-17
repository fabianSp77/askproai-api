<?php

namespace App\Http\Requests\CustomerPortal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancel Appointment Request Validation
 */
class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for cancellation.',
            'reason.min' => 'Please provide a more detailed reason (at least 10 characters).',
            'reason.max' => 'Cancellation reason cannot exceed 500 characters.',
        ];
    }
}
