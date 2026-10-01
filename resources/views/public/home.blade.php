@extends('layouts.public')

@section('title', $page->seoTitle())
@section('description', $page->seoDescription() ?? __('site.seo.default_description'))
@section('robots', $page->robotsDirective())
@section('og_image', $page->ogImageUrl())

@php
    $siteName = \App\Models\SiteSetting::current()->header_logo;
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            \App\Models\SiteSetting::current()->organizationSchema(),
            ['@type' => 'WebSite', '@id' => url('/').'#site', 'url' => url('/'), 'name' => $siteName, 'inLanguage' => 'uk-UA', 'publisher' => ['@id' => url('/').'#org']],
            ['@type' => 'WebPage', '@id' => url('/').'#webpage', 'url' => url('/'), 'name' => $page->seoTitle(), 'inLanguage' => 'uk-UA', 'isPartOf' => ['@id' => url('/').'#site'], 'dateModified' => $page->updated_at->toAtomString()],
            [
                '@type' => 'SoftwareApplication',
                'name' => $siteName,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Windows, macOS',
                'description' => $page->seoDescription(),
                'url' => url('/'),
                'inLanguage' => 'uk-UA',
                'publisher' => ['@id' => url('/').'#org'],
                'offers' => $packages->map(fn ($p) => [
                    '@type' => 'Offer',
                    'name' => $p->name,
                    'price' => (string) $p->price,
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                    'url' => url('/').'#pricing',
                ])->values()->all(),
            ],
        ],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<main>
    {!! $page->drawModules() !!}
</main>
@endsection
