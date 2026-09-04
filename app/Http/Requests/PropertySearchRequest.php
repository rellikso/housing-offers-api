<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city' => [
                'sometimes',
                'string',
            ],

            'check_in' => [
                'required',
                'date',
            ],

            'check_out' => [
                'required',
                'date',
                'after:check_in',
            ],

            'guests' => [
                'required',
                'integer',
                'min:1',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
                'uppercase',
            ],
        ];
    }
}
