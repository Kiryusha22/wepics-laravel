<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilenameCheckRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'not_regex:/\\/?%*:|"<>/']
        ];
    }
}
