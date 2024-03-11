<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccessRightRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'int|exists:users,id',
            'allow'   => 'boolean',
        ];
    }
}
