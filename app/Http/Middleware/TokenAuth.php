<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

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
            if (!($allowedLevel == 'guest'))
                throw new ApiException(401, 'Token not provided');
        }
        else {
            $user = User::getByToken($tokenValue);
            if ($allowedLevel == 'admin' &&
                !$user->is_admin)
                throw new ApiException(403, 'Forbidden for you');

            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return $next($request);
    }
}
