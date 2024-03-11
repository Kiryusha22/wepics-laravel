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
    public function handle(Request $request, Closure $next, $allowedLevel = 'user')
    {
        $tokenValue = $request->bearerToken();

        if (!$tokenValue) {
            if (!$allowedLevel == 'guest')
                throw new ApiException(401, 'Token not provided');
        }
        else {
            $tokenDB = Token::where('value', $tokenValue)->first();
            if (!$tokenDB)
                throw new ApiException(401, 'Invalid token');

            request()->sender = $tokenDB->user;
            if ($allowedLevel == 'admin' &&
                !request()->sender->is_admin)
                throw new ApiException(403, 'Forbidden for you');
        }

        return $next($request);
    }
}
