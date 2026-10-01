<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $pages = Page::published()->where('slug', '!=', Page::ROOT_SLUG)->indexable()->orderBy('id')->get();

        $coins = Coin::published()->indexable()->orderBy('id')->get();

        $home = Page::published()->where('slug', Page::ROOT_SLUG)->first();

        return response()->view('public.sitemap', ['pages' => $pages, 'coins' => $coins, 'homeUpdatedAt' => $home?->updated_at])->header('Content-Type', 'application/xml');
    }
}
