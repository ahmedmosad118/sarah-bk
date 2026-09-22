<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputMiddleware
{
    /**
     * Keys that should NOT be sanitized (e.g., passwords).
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
    ];

    /**
     * Handle an incoming request and sanitize inputs.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (!empty($input)) {
            $sanitized = $this->sanitizeArray($input);
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Recursively sanitize array values.
     */
    protected function sanitizeArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                $clean[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $clean[$key] = $this->sanitizeString($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * Strip null bytes and harmful script tags while preserving normal characters.
     */
    protected function sanitizeString(string $value): string
    {
        // 1. Remove Null Bytes
        $clean = str_replace(chr(0), '', $value);

        // 2. Strip dangerous executable script tags and iframes
        $clean = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $clean);
        $clean = preg_replace('/<\s*iframe\b[^>]*>(.*?)<\s*\/\s*iframe\s*>/is', '', $clean);
        $clean = preg_replace('/<\s*embed\b[^>]*>(.*?)<\s*\/\s*embed\s*>/is', '', $clean);
        $clean = preg_replace('/<\s*object\b[^>]*>(.*?)<\s*\/\s*object\s*>/is', '', $clean);

        // 3. Remove inline javascript event handlers (e.g., onload=, onerror=, onclick=) on tags
        $clean = preg_replace('/(<[^>]+?)\s+on[a-zA-Z]+\s*=\s*(["\']?[^"\'>\s]+["\']?)/i', '$1', $clean);

        // 4. Remove javascript: pseudo-protocols
        $clean = preg_replace('/javascript\s*:/i', '', $clean);

        return $clean;
    }
}
