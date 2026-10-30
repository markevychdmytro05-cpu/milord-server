<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalPath
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $uri = $request->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = $request->getQueryString();

        $canonical = $path;

        if (str_starts_with($canonical, '/index.php')) {
            $canonical = substr($canonical, strlen('/index.php')) ?: '/';
        }

        if ($canonical !== '/' && str_ends_with($canonical, '/')) {
            $canonical = rtrim($canonical, '/');
        }

        if ($canonical === '') {
            $canonical = '/';
        }

        if ($canonical !== $path) {
            return redirect()->to($canonical.($query !== null && $query !== '' ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
