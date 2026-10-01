@extends('layouts.public')

@section('title', $coin->seoTitle())
@section('description', $coin->seoDescription() ?? __('coins.seo.description_default', ['title' => $coin->title]))
@section('canonical', $coin->url())
@section('robots', $coin->robotsDirective())
@section('og_image', $coin->ogImageUrl())

@php
    $crumbs = [
        ['name' => __('site.breadcrumbs.home'), 'url' => route('home')],
        ['name' => __('coins.breadcrumb'), 'url' => route('coins.index')],
        ['name' => $coin->title, 'url' => $coin->url()],
    ];
    $facts = array_filter([
        __('coins.facts.denomination') => $coin->denomination,
        __('coins.facts.series') => $coin->series,
        __('coins.facts.metal') => $coin->metal,
        __('coins.facts.year') => $coin->release_year,
        __('coins.facts.mintage') => $coin->mintage !== null ? __('coins.facts.pcs', ['count' => number_format($coin->mintage, 0, ',', ' ')]) : null,
        __('coins.facts.price') => $coin->price !== null ? __('coins.facts.price_value', ['price' => number_format((float) $coin->price, 2, ',', ' ')]) : null,
        __('coins.facts.sale_start') => $coin->sale_starts_at ? __('coins.facts.kyiv_time', ['date' => $coin->sale_starts_at->locale(app()->getLocale())->translatedFormat('j F Y, H:i')]) : null,
        __('coins.facts.release') => $coin->sale_starts_at ? null : $coin->releaseLabel(),
    ], fn ($value) => filled($value));
    $nbu = ['@type' => 'Organization', 'name' => __('coins.issuer')];
    $product = $coin->price === null ? null : array_filter([
        '@type' => 'Product',
        'name' => $coin->title,
        'description' => $coin->seoDescription(),
        'image' => $coin->ogImageUrl(),
        'category' => __('coins.category'),
        'brand' => ['@type' => 'Brand', 'name' => __('coins.issuer')],
        'additionalProperty' => array_values(array_filter([
            $coin->denomination ? ['@type' => 'PropertyValue', 'name' => __('coins.schema.denomination'), 'value' => $coin->denomination] : null,
            $coin->metal ? ['@type' => 'PropertyValue', 'name' => __('coins.schema.metal'), 'value' => $coin->metal] : null,
            $coin->mintage !== null ? ['@type' => 'PropertyValue', 'name' => __('coins.schema.mintage'), 'value' => $coin->mintage, 'unitText' => __('coins.schema.unit')] : null,
        ])),
        'offers' => array_filter([
            '@type' => 'Offer',
            'url' => $coin->nbu_url ?: $coin->url(),
            'price' => number_format((float) $coin->price, 2, '.', ''),
            'priceCurrency' => 'UAH',
            'availability' => $coin->isUpcoming() ? 'https://schema.org/PreOrder' : 'https://schema.org/InStock',
            'availabilityStarts' => $coin->sale_starts_at?->toAtomString(),
            'seller' => $nbu,
        ]),
    ]);
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
                    '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url'],
                ])->all(),
            ],
            [
                '@type' => 'Article',
                'headline' => $coin->title,
                'description' => $coin->seoDescription(),
                'inLanguage' => 'uk-UA',
                'mainEntityOfPage' => $coin->url(),
                'dateModified' => $coin->updated_at->toAtomString(),
                'datePublished' => $coin->created_at->toAtomString(),
                'image' => $coin->ogImageUrl(),
                'author' => ['@id' => url('/').'#org'],
                'publisher' => \App\Models\SiteSetting::current()->organizationSchema(),
            ],
            ...($coin->image ? [array_filter([
                '@type' => 'ImageObject',
                'contentUrl' => $coin->imageUrl(),
                'url' => $coin->imageUrl(),
                'name' => __('coins.card.alt', ['name' => $coin->shortTitle()]),
                'caption' => $coin->title,
                'creditText' => $coin->image_source ? ($coin->image_credit ?: __('coins.photo.default_source')) : null,
                'creator' => $coin->image_source && ! $coin->image_credit ? $nbu : null,
                'representativeOfPage' => true,
            ])] : []),
            ...($product ? [$product] : []),
        ],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article class="page-article grid-bg">
        <div class="wrap">
            <div class="article-main">
                <nav class="crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                    @foreach ($crumbs as $crumb)
                        @if ($loop->last)
                            <span>{{ $crumb['name'] }}</span>
                        @else
                            <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                        @endif
                    @endforeach
                </nav>
                <h1 class="display">{{ $coin->title }}</h1>
                @if ($coin->excerpt)
                    <p class="page-lead">{{ $coin->excerpt }}</p>
                @endif
                @if ($coin->image)
                    <figure>
                        <img src="{{ $coin->image }}" alt="{{ __('coins.card.alt', ['name' => $coin->shortTitle()]) }}" loading="eager" style="max-width:100%;height:auto">
                        @if ($coin->image_source)
                            <figcaption>{{ __('coins.photo.credit') }}: <a href="{{ $coin->image_source }}" rel="nofollow noopener" target="_blank">{{ $coin->image_credit ?: __('coins.photo.default_source') }}</a></figcaption>
                        @endif
                    </figure>
                @endif
                @if ($facts)
                    <div class="prose">
                        <h2>{{ __('coins.facts.heading') }}</h2>
                        <table>
                            <tbody>
                                @foreach ($facts as $label => $value)
                                    <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($coin->content)
                    <div class="prose">{!! \App\Support\SafeHtml::clean($coin->content) !!}</div>
                @endif
                <div class="prose">
                    <h2>{{ __('coins.buy.heading') }}</h2>
                    <p>{{ $coin->isUpcoming() ? __('coins.buy.upcoming', ['date' => $coin->sale_starts_at->locale(app()->getLocale())->translatedFormat('j F Y, H:i')]) : __('coins.buy.default') }}
                        {!! __('coins.buy.body', ['brand' => '<a href="'.e(route('home')).'">'.e(\App\Models\SiteSetting::current()->header_logo).'</a>']) !!}</p>
                    <p><a href="{{ route('home') }}#pricing">{{ __('coins.buy.tariffs') }}</a>@if ($coin->nbu_url) · <a href="{{ $coin->nbu_url }}" rel="nofollow noopener" target="_blank">{{ __('coins.buy.nbu_link') }}</a>@endif</p>
                </div>
            </div>
        </div>
    </article>
@endsection
