<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiException extends HttpResponseException
{
    public function __construct(int $code, $message)
    {
        $data = [
            'code' => $code,
        ];
        if ($message)
            $data['message'] = $message;

        parent::__construct(response($data, $code));
    }
}
