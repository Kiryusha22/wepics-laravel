<?php

namespace App\Http\Requests;

use App\Enums\SortTypesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlbumImagesRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'page'      => 'int|min:1',
            'per_page'  => 'int|min:1',
            'sort'      => [Rule::enum(SortTypesEnum::class)],
            'tags'      => 'string',
            'reverse'   => 'nullable',
        ];
    }
}
