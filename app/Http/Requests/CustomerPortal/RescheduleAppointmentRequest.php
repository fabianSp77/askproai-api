<?php

namespace App\Http\Requests\CustomerPortal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reschedule Appointment Request Validation
 *
 * VALIDATION RULES:
 * - new_start_time: Required, ISO8601 format, future date
 * - reason: Optional, max 500 characters
 *
 * CUSTOM VALIDATION:
 * - Business hours check (handled in service layer)
 * - Minimum notice check (handled in service layer)
 * - Staff availability check (handled in service layer)
 */
class RescheduleAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled in controller via policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_start_time' => [
                'required',
                'date',
                'after:now',
                'date_format:Y-m-d\TH:i:sP', // ISO8601 with timezone
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'new_start_time.required' => 'Please specify the new appointment time.',
            'new_start_time.after' => 'The new appointment time must be in the future.',
            'new_start_time.date_format' => 'Invalid date format. Use ISO8601 format (e.g., 2025-11-25T10:00:00+01:00).',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'new_start_time' => 'new appointment time',
        ];
    }
}
