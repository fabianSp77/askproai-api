<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class RescheduleBookingRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => [
                'required',
                'date_format:Y-m-d\TH:i:s',
                function ($attribute, $value, $fail) {
                    $start = Carbon::parse($value);
                    if ($start->lte(now()->addHours(2))) {
                        $fail('The start time must be at least 2 hours from now');
                    }
                    if ($start->gte(now()->addDays(90))) {
                        $fail('The start time cannot be more than 90 days from now');
                    }
                }
            ],
            'timeZone' => ['required', 'string', 'timezone'],
            'reason' => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9ÄÖÜäöüß\s\-\.\,\!\?]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => 'New start time is required',
            'start.date_format' => 'Start time must be in format YYYY-MM-DDTHH:MM:SS',
            'timeZone.required' => 'Timezone is required',
            'timeZone.timezone' => 'Invalid timezone',
            'reason.max' => 'Reason cannot exceed 500 characters',
            'reason.regex' => 'Reason contains invalid characters',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator, 'Reschedule validation failed')
        );
    }

    protected function prepareForValidation()
    {
        if ($this->has('reason')) {
            $this->merge([
                'reason' => trim($this->reason)
            ]);
        }
    }
}