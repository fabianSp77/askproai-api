<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class RetellWebhookRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        // Authorization is handled by middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'array'],
            'payload.intent' => ['required', 'string', 'in:booking_create,booking_cancel,booking_reschedule,inquiry'],
            'payload.slots' => ['required', 'array'],
            'payload.slots.to_number' => ['nullable', 'string', 'regex:/^[\+0-9\-\s\(\)]+$/'],
            'payload.slots.callee' => ['nullable', 'string', 'regex:/^[\+0-9\-\s\(\)]+$/'],
            'payload.slots.name' => ['nullable', 'string', 'max:255'],
            'payload.slots.email' => ['nullable', 'email:rfc', 'max:255'],
            'payload.slots.start' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'payload.slots.end' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'payload.slots.timeZone' => ['nullable', 'string', 'timezone'],
            'payload.slots.language' => ['nullable', 'string', 'in:de,en,fr,it,es'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.required' => 'Webhook payload is required',
            'payload.intent.required' => 'Intent is required',
            'payload.intent.in' => 'Invalid intent type',
            'payload.slots.required' => 'Slots data is required',
            'payload.slots.to_number.regex' => 'Invalid phone number format',
            'payload.slots.callee.regex' => 'Invalid phone number format',
            'payload.slots.email.email' => 'Invalid email format',
            'payload.slots.start.date_format' => 'Start time must be in ISO 8601 format',
            'payload.slots.end.date_format' => 'End time must be in ISO 8601 format',
            'payload.slots.timeZone.timezone' => 'Invalid timezone',
            'payload.slots.language.in' => 'Unsupported language',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator, 'Webhook validation failed')
        );
    }

    protected function prepareForValidation()
    {
        // Sanitize email if present
        if ($this->has('payload.slots.email')) {
            $slots = $this->input('payload.slots');
            $slots['email'] = strtolower(trim($slots['email']));
            $this->merge([
                'payload' => array_merge($this->payload, ['slots' => $slots])
            ]);
        }

        // Sanitize phone numbers
        if ($this->has('payload.slots.to_number')) {
            $slots = $this->input('payload.slots');
            $slots['to_number'] = preg_replace('/\s+/', '', $slots['to_number']);
            $this->merge([
                'payload' => array_merge($this->payload, ['slots' => $slots])
            ]);
        }
    }

    /**
     * Get validated phone number from the request
     */
    public function getPhoneNumber(): ?string
    {
        $slots = $this->validated()['payload']['slots'];
        return $slots['to_number'] ?? $slots['callee'] ?? null;
    }

    /**
     * Get validated customer data
     */
    public function getCustomerData(): array
    {
        $slots = $this->validated()['payload']['slots'];
        return [
            'name' => $slots['name'] ?? 'Unbekannt',
            'email' => $slots['email'] ?? 'termin@askproai.de',
            'phone' => $this->getPhoneNumber(),
        ];
    }

    /**
     * Get validated booking data
     */
    public function getBookingData(): array
    {
        $slots = $this->validated()['payload']['slots'];
        return [
            'start' => $slots['start'] ?? null,
            'end' => $slots['end'] ?? null,
            'timeZone' => $slots['timeZone'] ?? 'Europe/Berlin',
            'language' => $slots['language'] ?? 'de',
        ];
    }
}