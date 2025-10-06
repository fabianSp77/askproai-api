<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalcomWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the signature middleware
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
                    'EVENT_TYPE.CREATED',
                    'EVENT_TYPE.UPDATED',
                    'EVENT_TYPE.DELETED',
                    'BOOKING.CREATED',
                    'BOOKING.UPDATED',
                    'BOOKING.CANCELLED',
                    'BOOKING.RESCHEDULED',
                    'BOOKING.REQUESTED',
                    'BOOKING.REJECTED',
                    'BOOKING.COMPLETED',
                    'ping'
                ])
            ],
            'payload' => 'required_unless:triggerEvent,ping|array',

            // Event Type payload validation
            'payload.id' => 'required_if:triggerEvent,EVENT_TYPE.CREATED,EVENT_TYPE.UPDATED,EVENT_TYPE.DELETED|integer',
            'payload.title' => 'required_if:triggerEvent,EVENT_TYPE.CREATED,EVENT_TYPE.UPDATED|string|max:255',
            'payload.slug' => 'sometimes|string|max:255',
            'payload.length' => 'sometimes|integer|min:5|max:480',
            'payload.hidden' => 'sometimes|boolean',
            'payload.price' => 'sometimes|numeric|min:0',
            'payload.currency' => 'sometimes|string|size:3',
            'payload.description' => 'sometimes|nullable|string|max:5000',
            'payload.locations' => 'sometimes|array',
            'payload.metadata' => 'sometimes|array',
            'payload.bookingFields' => 'sometimes|array',
            'payload.scheduleId' => 'sometimes|nullable|integer',

            // Booking payload validation (if needed)
            'payload.uid' => 'required_if:triggerEvent,BOOKING.CREATED,BOOKING.UPDATED,BOOKING.CANCELLED|string',
            'payload.eventTypeId' => 'required_if:triggerEvent,BOOKING.CREATED,BOOKING.UPDATED|integer',
            'payload.startTime' => 'required_if:triggerEvent,BOOKING.CREATED,BOOKING.UPDATED|date_format:Y-m-d\TH:i:s.v\Z',
            'payload.endTime' => 'required_if:triggerEvent,BOOKING.CREATED,BOOKING.UPDATED|date_format:Y-m-d\TH:i:s.v\Z',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'triggerEvent.required' => 'The webhook trigger event is required.',
            'triggerEvent.in' => 'Invalid webhook trigger event: :input',
            'payload.required_unless' => 'Payload is required for all events except ping.',
            'payload.id.required_if' => 'Event Type ID is required for this trigger event.',
            'payload.title.required_if' => 'Event Type title is required for CREATE and UPDATE events.',
            'payload.length.min' => 'Event duration must be at least 5 minutes.',
            'payload.length.max' => 'Event duration cannot exceed 480 minutes (8 hours).',
            'payload.price.min' => 'Price cannot be negative.',
            'payload.currency.size' => 'Currency must be a 3-letter ISO code.',
        ];
    }

    /**
     * Sanitize the validated data
     */
    public function sanitized(): array
    {
        $validated = $this->validated();

        // Sanitize string fields
        if (isset($validated['payload']['title'])) {
            $validated['payload']['title'] = strip_tags($validated['payload']['title']);
        }

        if (isset($validated['payload']['description'])) {
            $validated['payload']['description'] = strip_tags($validated['payload']['description']);
        }

        if (isset($validated['payload']['slug'])) {
            $validated['payload']['slug'] = preg_replace('/[^a-z0-9\-]/', '', strtolower($validated['payload']['slug']));
        }

        // Ensure numeric values are properly typed
        if (isset($validated['payload']['price'])) {
            $validated['payload']['price'] = (float) $validated['payload']['price'];
        }

        if (isset($validated['payload']['length'])) {
            $validated['payload']['length'] = (int) $validated['payload']['length'];
        }

        if (isset($validated['payload']['id'])) {
            $validated['payload']['id'] = (int) $validated['payload']['id'];
        }

        return $validated;
    }
}