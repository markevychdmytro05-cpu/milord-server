<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Package;
use App\Models\Page;
use App\Models\Release;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $pages = Page::published()->where('slug', '!=', Page::ROOT_SLUG)->indexable()->orderBy('id')->get();

        $coins = Coin::published()->withImage()->indexable()->orderBy('id')->get();

        $home = Page::published()->where('slug', Page::ROOT_SLUG)->first();

        $releasesAt = Release::active()->max('published_at');

        $homeUpdatedAt = $this->latest([
            $home?->updated_at,
            Package::forHome()->max('updated_at'),
            Coin::published()->where('show_on_home', true)->max('updated_at'),
            $releasesAt,
        ]);
        $articlesUpdatedAt = $pages->filter(fn (Page $page): bool => $page->isArticle())->max('updated_at');
        $lastmod = $pages->mapWithKeys(fn (Page $page): array => [
            $page->id => $page->slug === Page::BLOG_SLUG ? $this->latest([$page->updated_at, $articlesUpdatedAt]) : $page->updated_at,
        ]);

        return response()->view('public.sitemap', [
            'pages' => $pages,
            'lastmod' => $lastmod,
            'coins' => $coins,
            'homeUpdatedAt' => $homeUpdatedAt,
            'downloadsUpdatedAt' => $releasesAt ? Carbon::parse($releasesAt) : null,
        ])->header('Content-Type', 'application/xml');
    }

    private function latest(array $dates): ?Carbon
    {
        return collect($dates)->filter()->map(fn ($date): Carbon => Carbon::parse($date))->max();
    }
}
