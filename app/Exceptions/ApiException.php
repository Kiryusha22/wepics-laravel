<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiException extends HttpResponseException
{
    public function __construct(int $code, string $message, $errors = null)
    {
        $response = [
            'code' => $code,
            'message' => $message,
        ];
        if ($errors)
            $response['errors'] = $errors;

        parent::__construct(response($response, $code));
    }
}
