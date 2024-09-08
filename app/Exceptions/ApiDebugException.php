<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;

class ApiDebugException extends HttpResponseException
{
    public function __construct(...$args)
    {
        $code = 500;

        $response = [
            'code' => $code,
            'message' => "This ApiDebugException() call need REMOVE IN PRODUCTION!",
        ];
        for ($i = 1; $i <= count($args); $i++)
            $response["data$i"] = $args[$i - 1];

        parent::__construct(response($response, $code));
    }
}
