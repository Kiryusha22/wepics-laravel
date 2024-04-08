<?php

namespace App\Http\Requests;

class TagRenameRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'old_value' => 'required|string|not_regex:/,/',
            'new_value' => 'required|string|not_regex:/,/',
        ];
    }
    public function messages(): array {
        return [
            '*.not_regex' => 'Tag name cannot include a comma',
        ];
    }
}
