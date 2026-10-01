<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;
use App\Models\Page;
use App\Support\PaginationRedirect;
use Illuminate\Http\Exceptions\HttpResponseException;

class ArticlesModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        $base = Page::published()->whereNotNull('category')->where('slug', '!=', Page::ROOT_SLUG);
        $categories = (clone $base)->orderBy('category')->pluck('category')->unique()->values();
        $active = request()->query('category');
        $limit = max(3, min(60, (int) ($settings['limit'] ?? 12)));

        $articles = (clone $base)
            ->when($active && $categories->contains($active), fn ($query) => $query->where('category', $active))
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->paginate($limit)->onEachSide(1)->withQueryString();

        $redirect = PaginationRedirect::fromFirstPage(request()) ?? PaginationRedirect::fromOutOfRange(request(), $articles);
        if ($redirect) {
            throw new HttpResponseException($redirect);
        }

        return view('modules.articles', [
            'settings' => $settings,
            'articles' => $articles,
            'categories' => $categories,
            'active' => $categories->contains($active) ? $active : null,
        ])->render();
    }
}
