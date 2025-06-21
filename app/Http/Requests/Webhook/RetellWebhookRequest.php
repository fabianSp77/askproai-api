<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetellWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Already authorized by signature middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $baseRules = [
            'event' => [
                'required',
                'string',
                Rule::in(['call_started', 'call_ended', 'call_analyzed', 'call_inbound', 'call_outbound'])
            ],
            'call' => 'required|array',
            'call.call_id' => 'required|string|uuid',
            'call.agent_id' => 'required|string',
            'call.call_status' => 'sometimes|string',
            'call.call_type' => 'sometimes|string|in:inbound,outbound',
            'call.metadata' => 'sometimes|array',
        ];

        // Event-specific rules
        return match($this->input('event')) {
            'call_ended' => array_merge($baseRules, $this->callEndedRules()),
            'call_inbound' => array_merge($baseRules, $this->callInboundRules()),
            'call_analyzed' => array_merge($baseRules, $this->callAnalyzedRules()),
            default => $baseRules,
        };
    }

    /**
     * Rules specific to call_ended events
     */
    private function callEndedRules(): array
    {
        return [
            'call.call_summary' => 'sometimes|string|max:5000',
            'call.transcript' => 'sometimes|string|max:50000',
            'call.recording_url' => 'sometimes|url|max:500',
            'call.public_log_url' => 'sometimes|url|max:500',
            'call.start_timestamp' => 'required|integer|min:0',
            'call.end_timestamp' => 'required|integer|min:0',
            'call.duration' => 'sometimes|integer|min:0|max:7200', // Max 2 hours
            'call.duration_ms' => 'sometimes|integer|min:0',
            
            // Retell dynamic variables (German appointment booking)
            'call.retell_llm_dynamic_variables' => 'sometimes|array',
            'call.retell_llm_dynamic_variables.datum' => 'sometimes|nullable|date_format:Y-m-d',
            'call.retell_llm_dynamic_variables.uhrzeit' => ['sometimes', 'nullable', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'call.retell_llm_dynamic_variables.name' => 'sometimes|nullable|string|max:255',
            'call.retell_llm_dynamic_variables.telefon' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9\s\-\(\)\.]+$/'],
            'call.retell_llm_dynamic_variables.email' => 'sometimes|nullable|email|max:255',
            'call.retell_llm_dynamic_variables.dienstleistung' => 'sometimes|nullable|string|max:255',
            'call.retell_llm_dynamic_variables.mitarbeiter' => 'sometimes|nullable|string|max:255',
            'call.retell_llm_dynamic_variables.notizen' => 'sometimes|nullable|string|max:1000',
            'call.retell_llm_dynamic_variables.filiale' => 'sometimes|nullable|string|max:255',
            
            // Custom fields (with underscore prefix)
            'call.retell_llm_dynamic_variables._*' => 'sometimes|nullable|string|max:1000',
            
            // Call analysis
            'call.call_analysis' => 'sometimes|array',
            'call.call_analysis.user_sentiment' => 'sometimes|string|in:positive,negative,neutral,mixed',
            'call.call_analysis.call_summary' => 'sometimes|string|max:5000',
            'call.call_analysis.custom_analysis_data' => 'sometimes|array',
        ];
    }

    /**
     * Rules specific to call_inbound events
     */
    private function callInboundRules(): array
    {
        return [
            'call.from_number' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'call.to_number' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'call.direction' => 'required|string|in:inbound,outbound',
            'call.call_status' => 'required|string|in:ongoing,ended,error',
        ];
    }

    /**
     * Rules specific to call_analyzed events
     */
    private function callAnalyzedRules(): array
    {
        return [
            'call.call_analysis' => 'required|array',
            'call.call_analysis.user_sentiment' => 'sometimes|string|in:positive,negative,neutral,mixed',
            'call.call_analysis.call_successful' => 'sometimes|boolean',
            'call.call_analysis.custom_analysis_data' => 'sometimes|array',
            'call.call_analysis.in_voicemail' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event.required' => 'The event type is required',
            'event.in' => 'Invalid event type. Must be one of: call_started, call_ended, call_analyzed, call_inbound, call_outbound',
            'call.call_id.required' => 'The call ID is required',
            'call.call_id.uuid' => 'The call ID must be a valid UUID',
            'call.from_number.regex' => 'The from number must be a valid phone number',
            'call.to_number.regex' => 'The to number must be a valid phone number',
            'call.retell_llm_dynamic_variables.datum.date_format' => 'The date must be in format YYYY-MM-DD',
            'call.retell_llm_dynamic_variables.uhrzeit.regex' => 'The time must be in format HH:MM',
            'call.retell_llm_dynamic_variables.email.email' => 'Please provide a valid email address',
            'call.retell_llm_dynamic_variables.telefon.regex' => 'Please provide a valid phone number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone numbers before validation
        if ($this->has('call.from_number')) {
            $this->merge([
                'call' => array_merge($this->input('call', []), [
                    'from_number' => $this->normalizePhoneNumber($this->input('call.from_number'))
                ])
            ]);
        }

        if ($this->has('call.to_number')) {
            $this->merge([
                'call' => array_merge($this->input('call', []), [
                    'to_number' => $this->normalizePhoneNumber($this->input('call.to_number'))
                ])
            ]);
        }

        // Normalize dynamic variables phone number
        if ($this->has('call.retell_llm_dynamic_variables.telefon')) {
            $phone = $this->input('call.retell_llm_dynamic_variables.telefon');
            if ($phone) {
                $this->merge([
                    'call' => array_merge($this->input('call', []), [
                        'retell_llm_dynamic_variables' => array_merge(
                            $this->input('call.retell_llm_dynamic_variables', []),
                            ['telefon' => $this->normalizePhoneNumber($phone)]
                        )
                    ])
                ]);
            }
        }
    }

    /**
     * Basic phone number normalization
     */
    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters except +
        $normalized = preg_replace('/[^0-9+]/', '', $phone);

        // Ensure + is only at the beginning
        if (strpos($normalized, '+') > 0) {
            $normalized = str_replace('+', '', $normalized);
        }

        return $normalized;
    }

    /**
     * Get the validated data with additional processing
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Extract commonly used fields for easier access
        if (isset($validated['call'])) {
            $validated['call_id'] = $validated['call']['call_id'] ?? null;
            $validated['agent_id'] = $validated['call']['agent_id'] ?? null;
            
            // Extract appointment data from dynamic variables
            if (isset($validated['call']['retell_llm_dynamic_variables'])) {
                $validated['appointment_data'] = $this->extractAppointmentData($validated['call']['retell_llm_dynamic_variables']);
            }
        }

        return $validated;
    }

    /**
     * Extract appointment data from Retell dynamic variables
     */
    private function extractAppointmentData(array $dynamicVars): array
    {
        return [
            'date' => $dynamicVars['datum'] ?? null,
            'time' => $dynamicVars['uhrzeit'] ?? null,
            'customer_name' => $dynamicVars['name'] ?? null,
            'customer_phone' => $dynamicVars['telefon'] ?? null,
            'customer_email' => $dynamicVars['email'] ?? null,
            'service' => $dynamicVars['dienstleistung'] ?? null,
            'staff' => $dynamicVars['mitarbeiter'] ?? null,
            'notes' => $dynamicVars['notizen'] ?? null,
            'branch' => $dynamicVars['filiale'] ?? null,
        ];
    }
}