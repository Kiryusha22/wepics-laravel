<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'nickname' => 'required|string|min:1',
            'login'    => 'required|string|min:2|unique:users',
            'password' => 'required|string|min:8',
        ];
    }
}
