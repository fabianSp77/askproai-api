<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalcomWebhookRequest extends FormRequest
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
        return [
            'triggerEvent' => [
                'required',
                'string',
                Rule::in([
                    'BOOKING_CREATED',
                    'BOOKING_CONFIRMED', 
                    'BOOKING_CANCELLED',
                    'BOOKING_RESCHEDULED',
                    'BOOKING_REQUESTED',
                    'BOOKING_REJECTED',
                    'BOOKING_REQUESTED',
                    'FORM_SUBMITTED',
                    'MEETING_ENDED',
                    'RECORDING_READY'
                ])
            ],
            'createdAt' => 'required|date',
            'payload' => 'required|array',
            
            // Payload validation based on event type
            'payload.uid' => 'required|string',
            'payload.eventTypeId' => 'required|integer',
            'payload.title' => 'required|string|max:500',
            'payload.description' => 'sometimes|nullable|string|max:5000',
            'payload.startTime' => 'required|date',
            'payload.endTime' => 'required|date|after:payload.startTime',
            'payload.location' => 'sometimes|nullable|string|max:500',
            
            // Organizer information
            'payload.organizer' => 'required|array',
            'payload.organizer.id' => 'required|integer',
            'payload.organizer.name' => 'required|string|max:255',
            'payload.organizer.email' => 'required|email|max:255',
            'payload.organizer.timeZone' => 'required|string|max:50',
            'payload.organizer.language' => 'sometimes|array',
            'payload.organizer.language.locale' => 'sometimes|string|max:10',
            
            // Attendees information
            'payload.attendees' => 'required|array|min:1',
            'payload.attendees.*.id' => 'required|integer',
            'payload.attendees.*.email' => 'required|email|max:255',
            'payload.attendees.*.name' => 'required|string|max:255',
            'payload.attendees.*.timeZone' => 'required|string|max:50',
            'payload.attendees.*.language' => 'sometimes|array',
            'payload.attendees.*.language.locale' => 'sometimes|string|max:10',
            
            // Booking responses (custom fields)
            'payload.responses' => 'sometimes|array',
            'payload.responses.name' => 'sometimes|string|max:255',
            'payload.responses.email' => 'sometimes|email|max:255',
            'payload.responses.location' => 'sometimes|string|max:500',
            'payload.responses.notes' => 'sometimes|string|max:5000',
            'payload.responses.guests' => 'sometimes|array',
            'payload.responses.phone' => ['sometimes', 'string', 'regex:/^\+?[0-9\s\-\(\)\.]+$/'],
            
            // Metadata
            'payload.metadata' => 'sometimes|array',
            'payload.metadata.videoCallUrl' => 'sometimes|nullable|url|max:500',
            
            // Rescheduling specific fields
            'payload.rescheduledFromUid' => 'sometimes|nullable|string',
            'payload.rescheduleUid' => 'sometimes|nullable|string',
            
            // Cancellation specific fields
            'payload.cancellationReason' => 'sometimes|nullable|string|max:1000',
            
            // Team information
            'payload.teamId' => 'sometimes|nullable|integer',
            'payload.teamName' => 'sometimes|nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'triggerEvent.required' => 'The webhook event type is required',
            'triggerEvent.in' => 'Invalid webhook event type',
            'payload.uid.required' => 'The booking UID is required',
            'payload.startTime.required' => 'The booking start time is required',
            'payload.endTime.after' => 'The booking end time must be after the start time',
            'payload.attendees.required' => 'At least one attendee is required',
            'payload.attendees.*.email.email' => 'Each attendee must have a valid email address',
            'payload.responses.phone.regex' => 'Please provide a valid phone number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone numbers before validation
        if ($this->has('payload.responses.phone')) {
            $phone = $this->input('payload.responses.phone');
            if ($phone) {
                $this->merge([
                    'payload' => array_merge($this->input('payload', []), [
                        'responses' => array_merge(
                            $this->input('payload.responses', []),
                            ['phone' => $this->normalizePhoneNumber($phone)]
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
        if (isset($validated['payload'])) {
            $validated['booking_uid'] = $validated['payload']['uid'] ?? null;
            $validated['event_type_id'] = $validated['payload']['eventTypeId'] ?? null;
            $validated['start_time'] = $validated['payload']['startTime'] ?? null;
            $validated['end_time'] = $validated['payload']['endTime'] ?? null;
            
            // Extract first attendee as primary customer
            if (isset($validated['payload']['attendees'][0])) {
                $validated['customer'] = [
                    'name' => $validated['payload']['attendees'][0]['name'] ?? null,
                    'email' => $validated['payload']['attendees'][0]['email'] ?? null,
                    'phone' => $validated['payload']['responses']['phone'] ?? null,
                ];
            }
        }

        return $validated;
    }

    /**
     * Determine if this is a booking creation event
     */
    public function isBookingCreated(): bool
    {
        return in_array($this->input('triggerEvent'), ['BOOKING_CREATED', 'BOOKING_CONFIRMED']);
    }

    /**
     * Determine if this is a booking cancellation event
     */
    public function isBookingCancelled(): bool
    {
        return $this->input('triggerEvent') === 'BOOKING_CANCELLED';
    }

    /**
     * Determine if this is a booking reschedule event
     */
    public function isBookingRescheduled(): bool
    {
        return $this->input('triggerEvent') === 'BOOKING_RESCHEDULED';
    }
}