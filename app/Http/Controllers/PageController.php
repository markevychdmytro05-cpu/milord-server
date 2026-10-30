<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function page(string $slug): View|RedirectResponse
    {
        $redirect = $this->lowercaseRedirect($slug, 'pages.show');
        if ($redirect) {
            return $redirect;
        }

        $article = Page::published()->where('type', Page::TYPE_PAGE)->where('slug', $slug)->first();

        return $article?->isArticle() ? redirect()->route('articles.show', $article->slug, 301) : $this->render(Page::TYPE_PAGE, $slug);
    }

    public function article(string $slug): View|RedirectResponse
    {
        if ($redirect = $this->lowercaseRedirect($slug, 'articles.show')) {
            return $redirect;
        }

        $page = Page::published()->where('type', Page::TYPE_PAGE)->where('slug', $slug)->whereNotNull('category')->where('category', '!=', '')->firstOrFail();

        return $this->view($page);
    }

    public function service(string $slug): View|RedirectResponse
    {
        return $this->lowercaseRedirect($slug, 'services.show') ?? $this->render(Page::TYPE_SERVICE, $slug);
    }

    private function lowercaseRedirect(string $slug, string $route): ?RedirectResponse
    {
        return $slug === strtolower($slug) ? null : redirect()->route($route, strtolower($slug), 301);
    }

    private function render(string $type, string $slug): View
    {
        return $this->view(Page::published()->where('type', $type)->where('slug', $slug)->firstOrFail());
    }

    private function view(Page $page): View
    {
        return view('public.page', [
            'page' => $page,
            'related' => $page->category ? $page->relatedArticles(4) : collect(),
        ]);
    }
}
