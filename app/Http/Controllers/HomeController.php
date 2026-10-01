<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $page = Page::published()->where('type', Page::TYPE_PAGE)->where('slug', Page::ROOT_SLUG)->firstOrFail();

        return view('public.home', [
            'page' => $page,
            'packages' => Package::forHome()->get(),
        ]);
    }
}
