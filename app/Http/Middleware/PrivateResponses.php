<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivateResponses
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->is('app*', 'quotes*', 'livewire*', 'legal*')) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Referrer-Policy', 'no-referrer');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
