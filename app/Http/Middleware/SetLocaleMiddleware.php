<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request and set application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale')
            ?: $request->header('Accept-Language')
            ?: $request->query('locale')
            ?: 'ar';

        // Extract primary language tag if Accept-Language contains full string (e.g. "ar-SA,ar;q=0.9")
        if (str_contains($locale, ',')) {
            $locale = explode(',', $locale)[0];
        }
        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }
        if (str_contains($locale, ';')) {
            $locale = explode(';', $locale)[0];
        }

        $locale = strtolower(trim($locale));

        if (in_array($locale, ['ar', 'en'], true)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale('ar');
        }

        return $next($request);
    }
}
