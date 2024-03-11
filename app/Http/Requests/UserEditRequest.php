<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserEditRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'nickname' => 'string|min:1',
            'login'    => 'string|min:2|unique:users',
            'password' => 'string|min:8',
            'is_admin' => 'boolean',
        ];
    }
}
