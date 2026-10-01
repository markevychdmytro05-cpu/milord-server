<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function page(string $slug): View|RedirectResponse
    {
        return $this->lowercaseRedirect($slug, 'pages.show') ?? $this->render(Page::TYPE_PAGE, $slug);
    }

    public function service(string $slug): View|RedirectResponse
    {
        return $this->lowercaseRedirect($slug, 'services.show') ?? $this->render(Page::TYPE_SERVICE, $slug);
    }

    /** Адреса з великими літерами не дублюється, а постійно перенаправляється на канонічну. */
    private function lowercaseRedirect(string $slug, string $route): ?RedirectResponse
    {
        return $slug === strtolower($slug) ? null : redirect()->route($route, strtolower($slug), 301);
    }

    private function render(string $type, string $slug): View
    {
        $page = Page::published()->where('type', $type)->where('slug', $slug)->firstOrFail();

        return view('public.page', [
            'page' => $page,
            'related' => $page->category ? $page->relatedArticles(4) : collect(),
        ]);
    }
}
