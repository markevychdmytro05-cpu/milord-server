<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
@if ($homeUpdatedAt)
        <lastmod>{{ $homeUpdatedAt->toAtomString() }}</lastmod>
@endif
    </url>
@foreach ($pages as $page)
    <url>
        <loc>{{ $page->url() }}</loc>
        <lastmod>{{ $page->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
@if ($coins->isNotEmpty())
    <url>
        <loc>{{ route('coins.index') }}</loc>
        <lastmod>{{ $coins->max('updated_at')->toAtomString() }}</lastmod>
    </url>
@endif
@foreach ($coins as $coin)
    <url>
        <loc>{{ $coin->url() }}</loc>
        <lastmod>{{ $coin->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
