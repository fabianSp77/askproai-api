<?php

namespace App\Http\Requests\CustomerPortal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Accept Invitation Request Validation
 *
 * VALIDATION RULES:
 * - name: Required, max 255 characters
 * - email: Required, valid email format
 * - password: Required, min 8 characters, confirmed
 * - phone: Optional, max 20 characters
 * - terms_accepted: Required, must be true
 *
 * PASSWORD POLICY:
 * - Minimum 8 characters
 * - Must contain at least one letter
 * - Must contain at least one number
 * - Case-insensitive
 *
 * SECURITY:
 * - Email verification automatic via invitation
 * - Password confirmation required
 * - Terms acceptance mandatory
 */
class AcceptInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled in controller via token validation
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\d\s\+\-\(\)]+$/', // Allow digits, spaces, +, -, (, )
            ],
            'terms_accepted' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your full name.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name cannot exceed 255 characters.',

            'email.required' => 'Please provide your email address.',
            'email.email' => 'Please provide a valid email address.',

            'password.required' => 'Please provide a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',

            'phone.regex' => 'Please provide a valid phone number.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',

            'terms_accepted.required' => 'You must accept the terms and conditions.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'phone' => 'phone number',
            'terms_accepted' => 'terms and conditions',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->input('email'))),
            ]);
        }

        // Trim whitespace from name
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }

        // Normalize phone number (remove extra whitespace)
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\s+/', ' ', trim($this->input('phone'))),
            ]);
        }
    }
}
