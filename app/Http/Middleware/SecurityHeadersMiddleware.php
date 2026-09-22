<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach hardened security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent Clickjacking / UI Redressing
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enable legacy XSS filter in browsers that support it
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy for privacy and token protection
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict powerful browser capabilities
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        return $response;
    }
}
