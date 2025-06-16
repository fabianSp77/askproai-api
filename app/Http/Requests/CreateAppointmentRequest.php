<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CreateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_appointment');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:50',
            'staff_id' => [
                'required',
                'integer',
                Rule::exists('staff', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id)
                          ->where('is_active', true);
                }),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'calcom_event_type_id' => 'nullable|integer|exists:calcom_event_types,id',
            'source' => 'nullable|string|in:manual,phone,web,api',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Der Kundenname ist erforderlich.',
            'customer_phone.required' => 'Die Telefonnummer ist erforderlich.',
            'staff_id.required' => 'Bitte wählen Sie einen Mitarbeiter aus.',
            'staff_id.exists' => 'Der ausgewählte Mitarbeiter ist ungültig.',
            'branch_id.required' => 'Bitte wählen Sie einen Standort aus.',
            'starts_at.required' => 'Die Startzeit ist erforderlich.',
            'starts_at.after' => 'Die Startzeit muss in der Zukunft liegen.',
            'ends_at.after' => 'Die Endzeit muss nach der Startzeit liegen.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone number
        if ($this->has('customer_phone')) {
            $this->merge([
                'customer_phone' => preg_replace('/[^0-9+]/', '', $this->customer_phone),
            ]);
        }

        // Add company_id
        $this->merge([
            'company_id' => $this->user()->company_id,
        ]);

        // Calculate end time if duration is provided
        if ($this->has('starts_at') && $this->has('duration') && !$this->has('ends_at')) {
            $startsAt = Carbon::parse($this->starts_at);
            $this->merge([
                'ends_at' => $startsAt->copy()->addMinutes($this->duration),
            ]);
        }
    }
}