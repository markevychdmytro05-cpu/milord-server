{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $siteName }}: блог</title>
        <link>{{ route('pages.show', \App\Models\Page::BLOG_SLUG) }}</link>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml"/>
        <description>{{ __('site.seo.default_description') }}</description>
        <language>uk-UA</language>
@if ($updatedAt)
        <lastBuildDate>{{ $updatedAt->toRfc2822String() }}</lastBuildDate>
@endif
@foreach ($articles as $article)
        <item>
            <title>{{ $article->title }}</title>
            <link>{{ $article->url() }}</link>
            <guid isPermaLink="true">{{ $article->url() }}</guid>
            <pubDate>{{ $article->articleDate()->toRfc2822String() }}</pubDate>
            <category>{{ $article->category }}</category>
            <description>{{ $article->excerpt ?: $article->seoDescription() }}</description>
        </item>
@endforeach
    </channel>
</rss>
