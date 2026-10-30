<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $articles = Page::published()->indexable()->whereNotNull('category')->get()
            ->filter(fn (Page $page): bool => $page->isArticle())
            ->sortByDesc(fn (Page $page) => $page->articleDate())
            ->take(30)
            ->values();

        return response()->view('public.feed', [
            'articles' => $articles,
            'siteName' => SiteSetting::current()->header_logo,
            'updatedAt' => $articles->max(fn (Page $page) => $page->updated_at),
        ])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
