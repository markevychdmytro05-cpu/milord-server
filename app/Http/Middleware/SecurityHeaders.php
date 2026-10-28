<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базові заголовки безпеки для всіх відповідей. CSP тут м’яка (без обмеження скриптів і стилів),
 * щоб не зламати адмінку; HSTS додається лише на HTTPS.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // PHP додає цей заголовок сам і розкриває версію.
        header_remove('X-Powered-By');

        return $response;
    }
}
