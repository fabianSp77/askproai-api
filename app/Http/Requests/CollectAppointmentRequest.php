<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CollectAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by call context validation
        return true;
    }

    /**
     * Prepare the data for validation.
     * CRITICAL: This runs BEFORE validation, allowing us to sanitize problematic input
     */
    protected function prepareForValidation(): void
    {
        $args = $this->input('args', []);

        // Sanitize email BEFORE validation (remove spaces from speech-to-text)
        if (isset($args['email']) && is_string($args['email'])) {
            $args['email'] = str_replace(' ', '', trim($args['email']));
        }

        $this->merge(['args' => $args]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'args' => ['sometimes', 'array'],
            'args.datum' => ['nullable', 'string', 'max:30'],
            'args.date' => ['nullable', 'string', 'max:30'],
            'args.uhrzeit' => ['nullable', 'string', 'max:20'],
            'args.time' => ['nullable', 'string', 'max:20'],
            'args.name' => ['nullable', 'string', 'max:150'],
            'args.customer_name' => ['nullable', 'string', 'max:150'],
            'args.dienstleistung' => ['nullable', 'string', 'max:250'],
            'args.service' => ['nullable', 'string', 'max:250'],
            'args.call_id' => ['nullable', 'string', 'max:100'],
            'args.email' => ['nullable', 'email', 'max:255'],
            'args.bestaetigung' => ['nullable', 'boolean'],
            'args.confirm_booking' => ['nullable', 'boolean'],
            'args.duration' => ['nullable', 'integer', 'min:15', 'max:480'],
            // PHASE 2: Staff preference support
            'args.mitarbeiter' => ['nullable', 'string', 'max:150'],
            'args.staff' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'args.datum.max' => 'Datum ist zu lang',
            'args.date.max' => 'Date is too long',
            'args.uhrzeit.max' => 'Uhrzeit ist zu lang',
            'args.time.max' => 'Time is too long',
            'args.name.max' => 'Name ist zu lang (max 150 Zeichen)',
            'args.customer_name.max' => 'Name is too long (max 150 chars)',
            'args.email.email' => 'Ungültige E-Mail-Adresse',
            'args.email.max' => 'E-Mail ist zu lang',
            'args.duration.integer' => 'Duration must be a number',
            'args.duration.min' => 'Duration must be at least 15 minutes',
            'args.duration.max' => 'Duration cannot exceed 8 hours',
            'args.mitarbeiter.max' => 'Mitarbeiter-Name ist zu lang (max 150 Zeichen)',
            'args.staff.max' => 'Staff name is too long (max 150 chars)',
        ];
    }

    /**
     * Get sanitized and validated data
     */
    public function getAppointmentData(): array
    {
        $args = $this->input('args', []);

        return [
            'datum' => $this->sanitize($args['datum'] ?? $args['date'] ?? null),
            'uhrzeit' => $this->sanitize($args['uhrzeit'] ?? $args['time'] ?? null),
            'name' => $this->sanitize($args['name'] ?? $args['customer_name'] ?? ''),
            'dienstleistung' => $this->sanitize($args['dienstleistung'] ?? $args['service'] ?? ''),
            'call_id' => $args['call_id'] ?? null,
            'email' => $this->sanitizeEmail($args['email'] ?? null),
            'bestaetigung' => $args['bestaetigung'] ?? $args['confirm_booking'] ?? null,
            'duration' => $args['duration'] ?? 60,
            // PHASE 2: Staff preference support
            'mitarbeiter' => $this->sanitize($args['mitarbeiter'] ?? $args['staff'] ?? null),
        ];
    }

    /**
     * Sanitize string input
     */
    private function sanitize(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        // Remove HTML tags
        $cleaned = strip_tags($value);

        // Remove potentially dangerous characters but keep German umlauts
        $cleaned = preg_replace('/[<>{}\\\\]/', '', $cleaned);

        // Trim whitespace
        $cleaned = trim($cleaned);

        return $cleaned ?: null;
    }

    /**
     * Sanitize email input
     * Handles common speech-to-text errors like spaces in email addresses
     */
    private function sanitizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        // Remove HTML tags and trim
        $cleaned = trim(strip_tags($email));

        // CRITICAL FIX: Remove ALL spaces (common speech-to-text error)
        // Retell often transcribes "Fub Handy at Gmail" as "Fub Handy@Gmail.com"
        $cleaned = str_replace(' ', '', $cleaned);

        // Basic email validation
        if (filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return strtolower($cleaned);
        }

        return null;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        // Return 200 with error message (for Retell webhook compatibility)
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Ungültige Eingabedaten: ' . $validator->errors()->first(),
                'errors' => $validator->errors()->toArray()
            ], 200)
        );
    }
}
