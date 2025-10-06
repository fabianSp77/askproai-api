<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by Filament policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Call identification
            'external_id' => 'nullable|string|max:100',
            'retell_call_id' => 'nullable|string|max:100',
            'call_id' => 'nullable|string|max:100',

            // Customer & Company
            'customer_id' => 'nullable|exists:customers,id',
            'company_id' => 'nullable|exists:companies,id',
            'phone_number_id' => 'nullable|exists:phone_numbers,id',
            'agent_id' => 'nullable|exists:retell_agents,id',

            // Phone numbers - validated format
            'from_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+0-9\-\(\)\s]*$/'
            ],
            'to_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+0-9\-\(\)\s]*$/'
            ],

            // Duration & Timing
            'duration_sec' => 'nullable|integer|min:0|max:86400', // Max 24 hours
            'duration_ms' => 'nullable|integer|min:0',
            'wait_time_sec' => 'nullable|integer|min:0|max:3600', // Max 1 hour wait

            // Costs - all must be positive
            'cost' => 'nullable|numeric|min:0|max:10000',
            'cost_cents' => 'nullable|integer|min:0|max:1000000',
            'base_cost' => 'nullable|integer|min:0|max:100000',
            'reseller_cost' => 'nullable|integer|min:0|max:100000',
            'customer_cost' => 'nullable|integer|min:0|max:100000',
            'retell_cost' => 'nullable|numeric|min:0|max:10000',

            // Status fields with defined options
            'status' => [
                'nullable',
                Rule::in(['ongoing', 'completed', 'failed', 'missed', 'busy', 'no_answer', 'analyzed'])
            ],
            'call_status' => [
                'nullable',
                Rule::in(['ongoing', 'completed', 'ended', 'failed', 'missed', 'busy', 'no_answer', 'analyzed'])
            ],
            'direction' => [
                'nullable',
                Rule::in(['inbound', 'outbound'])
            ],

            // Sentiment & Analysis
            'sentiment' => [
                'nullable',
                Rule::in(['positive', 'neutral', 'negative'])
            ],
            'sentiment_score' => 'nullable|numeric|min:-1|max:1',

            // Booleans
            'call_successful' => 'nullable|boolean',
            'appointment_made' => 'nullable|boolean',
            'consent_given' => 'nullable|boolean',
            'data_forwarded' => 'nullable|boolean',
            'data_validation_completed' => 'nullable|boolean',
            'first_visit' => 'nullable|boolean',

            // Text fields with length limits
            'notes' => 'nullable|string|max:5000',
            'summary' => 'nullable|string|max:2000',
            'recording_url' => 'nullable|url|max:500',
            'transcript' => 'nullable|string',
            'disconnection_reason' => 'nullable|string|max:255',

            // JSON fields
            'raw' => 'nullable|json',
            'analysis' => 'nullable|json',
            'metadata' => 'nullable|json',
            'tags' => 'nullable|json',
            'action_items' => 'nullable|json',
            'custom_analysis_data' => 'nullable|json',
            'customer_data_backup' => 'nullable|json',
            'llm_token_usage' => 'nullable|json',
            'cost_breakdown' => 'nullable|json',

            // Timestamps
            'consent_at' => 'nullable|date',
            'forwarded_at' => 'nullable|date',
            'customer_data_collected_at' => 'nullable|date',
            'start_timestamp' => 'nullable|date',
            'end_timestamp' => 'nullable|date',

            // Business fields
            'lead_status' => 'nullable|string|max:50',
            'urgency_level' => [
                'nullable',
                Rule::in(['low', 'normal', 'high', 'urgent'])
            ],
            'no_show_count' => 'nullable|integer|min:0|max:100',
            'reschedule_count' => 'nullable|integer|min:0|max:100',
            'insurance_type' => 'nullable|string|max:100',
            'insurance_company' => 'nullable|string|max:100',
            'session_outcome' => 'nullable|string|max:100',
            'converted_appointment_id' => 'nullable|exists:appointments,id',
            'cost_calculation_method' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_number.regex' => 'Die Telefonnummer darf nur Zahlen, +, -, () und Leerzeichen enthalten.',
            'to_number.regex' => 'Die Telefonnummer darf nur Zahlen, +, -, () und Leerzeichen enthalten.',
            'duration_sec.min' => 'Die Dauer kann nicht negativ sein.',
            'duration_sec.max' => 'Die maximale Dauer beträgt 24 Stunden (86400 Sekunden).',
            'cost.min' => 'Die Kosten können nicht negativ sein.',
            'base_cost.min' => 'Die Basis-Kosten können nicht negativ sein.',
            'customer_cost.min' => 'Die Kunden-Kosten können nicht negativ sein.',
            'sentiment_score.min' => 'Der Sentiment-Score muss zwischen -1 und 1 liegen.',
            'sentiment_score.max' => 'Der Sentiment-Score muss zwischen -1 und 1 liegen.',
            'recording_url.url' => 'Die Aufnahme-URL muss eine gültige URL sein.',
            'customer_id.exists' => 'Der ausgewählte Kunde existiert nicht.',
            'company_id.exists' => 'Die ausgewählte Firma existiert nicht.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean phone numbers
        if ($this->has('from_number')) {
            $this->merge([
                'from_number' => $this->cleanPhoneNumber($this->from_number)
            ]);
        }

        if ($this->has('to_number')) {
            $this->merge([
                'to_number' => $this->cleanPhoneNumber($this->to_number)
            ]);
        }

        // Ensure status consistency
        if ($this->has('status') && !$this->has('call_status')) {
            $this->merge([
                'call_status' => $this->status
            ]);
        }
    }

    /**
     * Clean and standardize phone numbers
     */
    private function cleanPhoneNumber(?string $number): ?string
    {
        if (!$number) {
            return null;
        }

        // Remove excessive spaces
        $number = preg_replace('/\s+/', ' ', trim($number));

        // Truncate if too long
        if (strlen($number) > 20) {
            $number = substr($number, 0, 20);
        }

        return $number;
    }
}