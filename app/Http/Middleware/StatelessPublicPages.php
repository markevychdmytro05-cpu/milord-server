<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Публічні сторінки гостя не мають ні сесії, ні cookie: без цього їх не можна кешувати на CDN чи nginx,
 * а кожен візит бота не пише рядок у таблицю сесій.
 *
 * Спрацьовує лише для GET/HEAD поза адмінкою й API та лише коли в запиті немає cookie сесії
 * (вона зʼявляється після відправки форми, щоб показати помилки й підтвердження).
 */
class StatelessPublicPages
{
    /** Маршрути, відповідь яких однакова для всіх гостей і її безпечно віддавати з кешу. */
    private const CACHEABLE_ROUTES = [
        'home', 'pages.show', 'articles.show', 'services.show', 'coins.index', 'coins.show',
        'downloads.index', 'sitemap', 'feed', 'robots', 'llms', 'llms.full',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $stateless = $this->isStateless($request);

        if ($stateless) {
            // Драйвер array не пише в сховище й не ставить cookie сесії.
            config(['session.driver' => 'array']);
        }

        $response = $next($request);

        if (! $stateless) {
            return $response;
        }

        // Токен XSRF не потрібен: форма заявки перевіряється за походженням запиту.
        $response->headers->removeCookie('XSRF-TOKEN');
        $response->headers->removeCookie(config('session.cookie'));

        if ($response->getStatusCode() === 200 && in_array($request->route()?->getName(), self::CACHEABLE_ROUTES, true)) {
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=300, stale-while-revalidate=60');

            // ETag і 304: Googlebot та браузери не качають незмінну сторінку вдруге.
            if ($request->isMethod('GET') && ! $response->isRedirection() && is_string($response->getContent())) {
                $response->setEtag(md5($response->getContent()));
                $response->isNotModified($request);
            }
        }

        return $response;
    }

    private function isStateless(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'api/*', 'up') || $request->hasCookie(config('session.cookie'))) {
            return false;
        }

        return true;
    }
}
