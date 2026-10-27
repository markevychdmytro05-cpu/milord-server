@extends('layouts.public')

@section('title', $page->seoTitle())
@section('description', $page->seoDescription() ?? __('site.seo.default_description'))
@section('canonical', $page->url().(request()->integer('page') > 1 ? '?page='.request()->integer('page') : ''))
@section('robots', $page->robotsDirective())
@section('og_image', $page->ogImageUrl())
@if ($page->category)
    @section('og_type', 'article')
@endif

@php
    $crumbs = [['name' => __('site.breadcrumbs.home'), 'url' => route('home')]];
    if ($page->type === \App\Models\Page::TYPE_SERVICE) {
        $crumbs[] = ['name' => __('site.breadcrumbs.features'), 'url' => route('home').'#features'];
    }
    if ($page->isArticle()) {
        $crumbs[] = ['name' => __('site.breadcrumbs.knowledge_base'), 'url' => route('pages.show', 'baza-znan')];
    }
    $crumbs[] = ['name' => $page->title, 'url' => $page->url()];
    $faqItems = [];
    if (preg_match('~<h2[^>]*>\s*'.preg_quote(__('site.faq_heading'), '~').'\s*</h2>(.*?)(?=<h2|$)~su', (string) $page->content, $faqSection)) {
        preg_match_all('~<h3[^>]*>(.*?)</h3>\s*<p>(.*?)</p>~su', $faqSection[1], $faqPairs, PREG_SET_ORDER);
        foreach ($faqPairs as $pair) {
            $faqItems[] = [
                '@type' => 'Question',
                'name' => trim(html_entity_decode(strip_tags($pair[1]), ENT_QUOTES | ENT_HTML5)),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(html_entity_decode(strip_tags($pair[2]), ENT_QUOTES | ENT_HTML5))],
            ];
        }
    }
    // Article лише для статей бази знань; решта сторінок (послуги, про нас, документи, хаб) – WebPage.
    $main = $page->isArticle()
        ? [
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $page->seoDescription(),
            'inLanguage' => 'uk-UA',
            'mainEntityOfPage' => $page->url(),
            'dateModified' => $page->updated_at->toAtomString(),
            'datePublished' => $page->articleDate()->toAtomString(),
            'image' => $page->ogImageUrl(),
            'author' => ['@type' => 'Organization', 'name' => \App\Models\SiteSetting::current()->header_logo, 'url' => url('/')],
        ]
        : [
            '@type' => $page->slug === 'baza-znan' ? 'CollectionPage' : 'WebPage',
            'name' => $page->title,
            'url' => $page->url(),
            'description' => $page->seoDescription(),
            'inLanguage' => 'uk-UA',
            'isPartOf' => ['@id' => url('/').'#site'],
        ];
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
                    '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url'],
                ])->all(),
            ],
            array_filter($main, fn ($value) => $value !== null && $value !== ''),
            ...(count($faqItems) >= 2 ? [['@type' => 'FAQPage', 'mainEntity' => $faqItems]] : []),
        ],
    ];
@endphp

@push('head')
    @if ($page->category)
        <meta property="article:published_time" content="{{ $page->articleDate()->toAtomString() }}">
        <meta property="article:modified_time" content="{{ $page->updated_at->toAtomString() }}">
        <meta property="article:section" content="{{ $page->category }}">
    @endif
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article class="page-article {{ $page->show_title ? '' : 'is-compact' }}">
        <div class="wrap {{ $related->isNotEmpty() ? 'has-aside' : '' }}">
            <div class="article-main">
            <nav class="crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <ol>
                    @foreach ($crumbs as $crumb)
                        @if ($loop->last)
                            <li aria-current="page">{{ $crumb['name'] }}</li>
                        @else
                            <li><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @if ($page->show_title)
                <h1 class="display">{{ $page->title }}</h1>
                @if ($page->excerpt)
                    <p class="page-lead">{{ $page->excerpt }}</p>
                @endif
                @if ($page->isArticle())
                    <div class="article-meta">
                        <span class="kb-tag kb-c-{{ \App\Models\Page::categoryColor($page->category) }}">{{ $page->category }}</span>
                        <time class="kb-date" datetime="{{ $page->articleDate()->toDateString() }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ __('site.date_with_year', ['date' => $page->articleDate()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</time>
                    </div>
                    @if ($page->og_image)
                        @php($coverPath = parse_url($page->ogImageUrl(), PHP_URL_PATH) ?: '')
                        @php($coverBase = preg_replace('/\.(jpe?g|png)$/i', '', $coverPath))
                        @php($coverWebp = $coverBase !== $coverPath && is_file(public_path(ltrim($coverBase, '/').'-960.webp')))
                        <figure class="article-cover">
                            @if ($coverWebp)<picture><source type="image/webp" srcset="{{ $coverBase }}-640.webp 640w, {{ $coverBase }}-960.webp 960w" sizes="(min-width: 1024px) min(720px, calc(95vw - 400px)), (min-width: 640px) min(720px, calc(100vw - 64px)), calc(100vw - 32px)">@endif
                            <img src="{{ $coverWebp ? $coverPath : $page->ogImageUrl() }}" alt="" width="1200" height="750" fetchpriority="high" decoding="async">
                            @if ($coverWebp)</picture>@endif
                        </figure>
                    @endif
                @endif
            @endif
            @if ($page->content)
                <div class="prose">{!! \App\Support\SafeHtml::clean($page->content) !!}</div>
            @endif
            </div>

            @if ($related->isNotEmpty())
                <aside class="read-also" aria-label="{{ __('site.read_also') }}">
                    <h2>{{ __('site.read_also') }}</h2>
                    @foreach ($related as $item)
                        <a class="ra-card" href="{{ $item->url() }}">
                            <span class="kb-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ __('site.date_with_year', ['date' => $item->articleDate()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</span>
                            <span class="kb-tags"><span class="kb-tag kb-c-{{ \App\Models\Page::categoryColor($item->category) }}">{{ $item->category }}</span></span>
                            <strong>{{ $item->title }}</strong>
                            @if ($item->excerpt)<span class="ra-excerpt">{{ $item->excerpt }}</span>@endif
                        </a>
                    @endforeach
                </aside>
            @endif
        </div>
    </article>

    {!! $page->drawModules() !!}
@endsection
