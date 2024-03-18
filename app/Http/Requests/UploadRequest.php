<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'images' => 'required|array',
        ];
    }
}
