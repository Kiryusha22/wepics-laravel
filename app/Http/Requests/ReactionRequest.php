<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReactionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'reactions'=>'required|array'
        ];
    }
}
