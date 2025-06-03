<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;            // add auth later if you need it
    }

    public function rules(): array
    {
        return [
            'uid' => [
                'required',
                'string',
                'max:32',
                'regex:/^[0-9A-F]+$/'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'uid.regex' => 'UID must be uppercase hexadecimal (0-9, A-F).',
        ];
    }
}
