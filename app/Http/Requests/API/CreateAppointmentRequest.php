<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'uuid',
                Rule::exists('customers', 'id')->where('company_id', $this->user()->company_id ?? null)
            ],
            'service_id' => [
                'required', 
                'uuid',
                Rule::exists('services', 'id')->where('company_id', $this->user()->company_id ?? null)
            ],
            'staff_id' => [
                'required',
                'uuid', 
                Rule::exists('staff', 'id')->where('company_id', $this->user()->company_id ?? null)
            ],
            'branch_id' => [
                'required',
                'uuid',
                Rule::exists('branches', 'id')->where('company_id', $this->user()->company_id ?? null)
            ],
            'starts_at' => [
                'required',
                'date',
                'after:now',
                'date_format:Y-m-d H:i:s'
            ],
            'ends_at' => [
                'required',
                'date',
                'after:starts_at',
                'date_format:Y-m-d H:i:s'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'metadata' => [
                'nullable',
                'array'
            ],
            'metadata.*' => [
                'string',
                'max:255'
            ],
            'send_confirmation' => [
                'boolean'
            ],
            'source' => [
                'nullable',
                'string',
                'in:manual,phone,web,api'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Der ausgewählte Kunde existiert nicht.',
            'service_id.exists' => 'Die ausgewählte Leistung existiert nicht.',
            'staff_id.exists' => 'Der ausgewählte Mitarbeiter existiert nicht.',
            'branch_id.exists' => 'Die ausgewählte Filiale existiert nicht.',
            'starts_at.after' => 'Der Termin muss in der Zukunft liegen.',
            'ends_at.after' => 'Das Ende muss nach dem Start liegen.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ensure proper date format
        if ($this->has('starts_at') && !str_contains($this->starts_at, ':')) {
            $this->merge([
                'starts_at' => $this->starts_at . ' 00:00:00'
            ]);
        }
        
        if ($this->has('ends_at') && !str_contains($this->ends_at, ':')) {
            $this->merge([
                'ends_at' => $this->ends_at . ' 00:00:00'
            ]);
        }
        
        // Set default source
        if (!$this->has('source')) {
            $this->merge(['source' => 'api']);
        }
    }
}