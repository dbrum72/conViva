<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyPasswordToken
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('api')->getToken()) {
            $claim = Auth::guard('api')->payload()->get('pwd');
            $expected = $request->user()->getJWTCustomClaims()['pwd'];
            abort_unless(is_string($claim) && hash_equals($expected, $claim), 401, 'Sua sessão expirou. Entre novamente.');
        }

        return $next($request);
    }
}
