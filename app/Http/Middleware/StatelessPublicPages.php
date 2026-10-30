<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StatelessPublicPages
{
    private const CACHEABLE_ROUTES = [
        'home', 'pages.show', 'articles.show', 'services.show', 'coins.index', 'coins.show',
        'downloads.index', 'sitemap', 'feed', 'robots', 'llms', 'llms.full',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $stateless = $this->isStateless($request);

        if ($stateless) {
            config(['session.driver' => 'array']);
        }

        $response = $next($request);

        if (! $stateless) {
            return $response;
        }

        $response->headers->removeCookie('XSRF-TOKEN');
        $response->headers->removeCookie(config('session.cookie'));

        if ($response->getStatusCode() === 200 && in_array($request->route()?->getName(), self::CACHEABLE_ROUTES, true)) {
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=300, stale-while-revalidate=60');

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
