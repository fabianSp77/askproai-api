<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class PushEventTypesRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.integer' => 'Company ID must be a number',
            'company_id.exists' => 'The specified company does not exist',
            'branch_id.integer' => 'Branch ID must be a number',
            'branch_id.exists' => 'The specified branch does not exist',
            'service_id.integer' => 'Service ID must be a number',
            'service_id.exists' => 'The specified service does not exist',
            'force.boolean' => 'Force parameter must be true or false',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator, 'Event type push validation failed')
        );
    }

    protected function prepareForValidation()
    {
        // Convert string booleans to actual booleans
        if ($this->has('force')) {
            $this->merge([
                'force' => filter_var($this->force, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}