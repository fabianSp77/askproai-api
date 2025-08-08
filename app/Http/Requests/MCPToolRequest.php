<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MCPToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Already handled by middleware
    }

    public function rules(): array
    {
        return [
            'tool' => ['required', 'string', Rule::in([
                'getCurrentTimeBerlin',
                'checkAvailableSlots', 
                'bookAppointment',
                'getCustomerInfo',
                'endCallSession'
            ])],
            'arguments' => ['array'],
            'call_id' => ['sometimes', 'string', 'max:255'],
            'arguments.company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'arguments.date' => ['sometimes', 'string'],
            'arguments.name' => ['sometimes', 'string', 'max:255'],
            'arguments.phone' => ['sometimes', 'string', 'max:50'],
            'arguments.email' => ['sometimes', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tool.in' => 'Unknown tool: :input',
            'arguments.company_id.exists' => 'Company not found',
        ];
    }
}