<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRedirects
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('[MIDDLEWARE] Request incoming', [
            'url' => $request->url(),
            'method' => $request->method(),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
        ]);

        $response = $next($request);

        if ($response->isRedirection()) {
            Log::info('[MIDDLEWARE] Redirect detected', [
                'from' => $request->url(),
                'to' => $response->headers->get('Location'),
                'status' => $response->getStatusCode(),
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id(),
            ]);
        }

        return $response;
    }
}
