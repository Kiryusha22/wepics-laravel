<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Token;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $tokenValue = $request->bearerToken();
        if (!$tokenValue) {
            throw new ApiException(401, 'Token not provided');
        }

        $token = Token::where('value', $tokenValue)->first();
        if (!$token) {
            throw new ApiException(401, 'Invalid token');
        }

        $request->sender = $token->user;
        return $next($request);
    }

}
