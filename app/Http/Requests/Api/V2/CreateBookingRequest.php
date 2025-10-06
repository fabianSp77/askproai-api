<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class CreateBookingRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÄÖÜäöüß\s\-\.]+$/'],
            'customer.email' => ['required', 'email:rfc,dns', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+0-9\-\s\(\)]+$/'],
            'timeZone' => ['required', 'string', 'timezone'],
            'start' => ['required', 'date_format:Y-m-d\TH:i:s', 'after:now'],
            'segments' => ['nullable', 'array'],
            'segments.*.staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'source' => ['nullable', 'string', 'in:api,web,phone,walk-in'],
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'Service ID is required',
            'service_id.exists' => 'The specified service does not exist',
            'branch_id.required' => 'Branch ID is required',
            'branch_id.exists' => 'The specified branch does not exist',
            'customer.required' => 'Customer information is required',
            'customer.name.required' => 'Customer name is required',
            'customer.name.regex' => 'Customer name contains invalid characters',
            'customer.email.required' => 'Customer email is required',
            'customer.email.email' => 'Invalid email format',
            'customer.phone.regex' => 'Phone number contains invalid characters',
            'timeZone.timezone' => 'Invalid timezone',
            'start.required' => 'Start time is required',
            'start.date_format' => 'Start time must be in format YYYY-MM-DDTHH:MM:SS',
            'start.after' => 'Start time must be in the future',
            'source.in' => 'Invalid booking source',
        ];
    }

    /**
     * Handle a failed validation attempt
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator, 'Booking validation failed')
        );
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Sanitize input data
        if ($this->has('customer.name')) {
            $this->merge([
                'customer' => array_merge($this->customer, [
                    'name' => trim($this->customer['name'])
                ])
            ]);
        }

        if ($this->has('customer.email')) {
            $this->merge([
                'customer' => array_merge($this->customer, [
                    'email' => strtolower(trim($this->customer['email']))
                ])
            ]);
        }

        if ($this->has('customer.phone')) {
            $this->merge([
                'customer' => array_merge($this->customer, [
                    'phone' => preg_replace('/\s+/', '', $this->customer['phone'])
                ])
            ]);
        }
    }
}