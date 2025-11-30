<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BearerTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedHttpException('Bearer', 'Missing bearer token');
        }

        $token = substr($header, 7);
        $expected = (string) config('app.api_token');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token');
        }

        return $next($request);
    }
}
