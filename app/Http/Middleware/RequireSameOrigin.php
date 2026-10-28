<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Захист публічної форми без CSRF-токена (токен вимагає сесії, а сторінки віддаються без неї):
 * браузерний запит має прийти з нашого ж сайту. Решту відсікають ліміт запитів і приманка в формі.
 */
class RequireSameOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->header('Sec-Fetch-Site');

        if ($site !== null && ! in_array($site, ['same-origin', 'none'], true)) {
            abort(403);
        }

        $origin = $request->header('Origin');

        if ($origin !== null && $origin !== 'null' && parse_url($origin, PHP_URL_HOST) !== $request->getHost()) {
            abort(403);
        }

        return $next($request);
    }
}
