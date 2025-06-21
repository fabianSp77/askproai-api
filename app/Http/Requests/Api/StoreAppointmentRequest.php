<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\PhoneNumber;
use App\Rules\SafeString;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'string', 'max:255', new SafeString()],
            'customer_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'string', new PhoneNumber()],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:1000', new SafeString()],
            'source' => ['nullable', 'string', Rule::in(['phone', 'web', 'mobile', 'api'])],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['string', 'max:255', new SafeString()],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'customer_name.required_without' => 'Customer name is required when customer ID is not provided.',
            'customer_phone.required_without' => 'Customer phone is required when customer ID is not provided.',
            'start_time.after' => 'The appointment must be scheduled for a future time.',
            'end_time.after' => 'The end time must be after the start time.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone number
        if ($this->has('customer_phone')) {
            $this->merge([
                'customer_phone' => preg_replace('/[^0-9+]/', '', $this->customer_phone)
            ]);
        }

        // Ensure proper datetime format
        foreach (['start_time', 'end_time'] as $field) {
            if ($this->has($field)) {
                try {
                    $date = new \DateTime($this->input($field));
                    $this->merge([$field => $date->format('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    // Let validation handle invalid dates
                }
            }
        }
    }
}