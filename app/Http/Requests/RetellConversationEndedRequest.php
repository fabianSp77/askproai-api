<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetellConversationEndedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // evtl. später Signatur-Middleware
    }

    public function rules(): array
    {
        return [
            'call_id'     => ['required', 'uuid'],
            'tmp_call_id' => ['nullable', 'uuid'],
            'duration'    => ['required', 'integer', 'min:0'],
        ];
    }
}
