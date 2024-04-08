<?php

namespace App\Http\Requests;

class TagRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'tag' => 'required|string|not_regex:/,/',
        ];
    }
    public function messages(): array {
        return [
            'tag.not_regex' => 'Tag name cannot include a comma',
        ];
    }
}
