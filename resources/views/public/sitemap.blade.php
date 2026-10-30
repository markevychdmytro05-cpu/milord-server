<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.sitemaps.org/schemas/sitemap-image/1.1">
    <url>
        <loc>{{ route('home') }}</loc>
@if ($homeUpdatedAt)
        <lastmod>{{ $homeUpdatedAt->toAtomString() }}</lastmod>
@endif
    </url>
@foreach ($pages as $page)
    <url>
        <loc>{{ $page->url() }}</loc>
        <lastmod>{{ $lastmod[$page->id]->toAtomString() }}</lastmod>
@if ($page->og_image)
        <image:image>
            <image:loc>{{ $page->ogImageUrl() }}</image:loc>
        </image:image>
@endif
    </url>
@endforeach
@if ($downloadsUpdatedAt)
    <url>
        <loc>{{ route('downloads.index') }}</loc>
        <lastmod>{{ $downloadsUpdatedAt->toAtomString() }}</lastmod>
    </url>
@endif
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
