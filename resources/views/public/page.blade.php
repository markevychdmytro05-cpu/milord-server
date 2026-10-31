@extends('layouts.public')

@php
    $isBlogHub = $page->slug === \App\Models\Page::BLOG_SLUG;
    $pageNumber = $isBlogHub ? max(1, request()->integer('page')) : 1;
    $isFiltered = $isBlogHub && request()->filled('category');
    $pageSuffix = $pageNumber > 1 ? ' – '.__('site.pagination.page', ['number' => $pageNumber]) : '';
@endphp
@section('title', $page->seoTitle().$pageSuffix)
@section('description', ($page->seoDescription() ?? __('site.seo.default_description')).($pageNumber > 1 ? ' '.__('site.pagination.page', ['number' => $pageNumber]) : ''))
@section('canonical', $page->url().(! $isFiltered && $pageNumber > 1 ? '?page='.$pageNumber : ''))
@section('robots', $isFiltered ? 'noindex, follow' : $page->robotsDirective())
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
        $crumbs[] = ['name' => __('site.breadcrumbs.knowledge_base'), 'url' => route('pages.show', \App\Models\Page::BLOG_SLUG)];
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
            'articleSection' => $page->category,
            'wordCount' => str_word_count(strip_tags((string) $page->content), 0, 'абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯ’'),
            'author' => ['@id' => url('/').'#author'],
            'publisher' => ['@id' => url('/').'#org'],
            'isPartOf' => ['@id' => url('/').'#site'],
        ]
        : [
            '@type' => $page->slug === \App\Models\Page::BLOG_SLUG ? 'CollectionPage' : 'WebPage',
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
            \App\Models\SiteSetting::current()->organizationSchema(),
            ...($page->isArticle() ? [\App\Models\SiteSetting::current()->authorSchema()] : []),
            ['@type' => 'WebSite', '@id' => url('/').'#site', 'url' => url('/'), 'name' => \App\Models\SiteSetting::current()->header_logo, 'alternateName' => parse_url(url('/'), PHP_URL_HOST), 'inLanguage' => 'uk-UA', 'publisher' => ['@id' => url('/').'#org']],
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
    @php
        $showSidebar = $page->show_title && $related->isEmpty()
            && ($page->type === \App\Models\Page::TYPE_SERVICE || $page->slug === 'pro-nas');
        $usefulPages = $showSidebar
            ? \App\Models\Page::published()->whereIn('slug', ['iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia', 'chek-lyst-pered-startom-prodazhu-monety-nbu', 'skilky-koshtuie-bot-dlia-kupivli-monet-nbu', 'litsenziia-numis-aktyvatsiia-prystriy-oflayn-dostup'])->get()
            : collect();
    @endphp
    <article class="page-article {{ $page->show_title ? '' : 'is-compact' }}">
        <div class="wrap {{ $related->isNotEmpty() || $showSidebar ? 'has-aside' : '' }}">
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
                        <span class="kb-author">{{ __('site.author_label') }} <a href="{{ route('pages.show', 'pro-nas') }}" rel="author">{{ config('site.author_name') }}</a></span>
                        <time class="kb-date" datetime="{{ $page->articleDate()->toDateString() }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ __('site.date_with_year', ['date' => $page->articleDate()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</time>
                        @if ($page->articleDate()->diffInDays($page->updated_at) >= 1)

                            <time class="kb-date kb-date--updated" datetime="{{ $page->updated_at->toDateString() }}">{{ __('site.updated_on', ['date' => $page->updated_at->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</time>

                        @endif
                    </div>
                    @if ($page->og_image)
                        @php($coverPath = parse_url($page->ogImageUrl(), PHP_URL_PATH) ?: '')
                        @php($coverBase = preg_replace('/\.(jpe?g|png)$/i', '', $coverPath))
                        @php($coverWebp = $coverBase !== $coverPath && is_file(public_path(ltrim($coverBase, '/').'-960.webp')))
                        <figure class="article-cover">
                            @if ($coverWebp)<picture><source type="image/webp" srcset="{{ $coverBase }}-640.webp 640w, {{ $coverBase }}-960.webp 960w" sizes="(min-width: 1024px) min(720px, calc(95vw - 400px)), (min-width: 640px) min(720px, calc(100vw - 64px)), calc(100vw - 32px)">@endif
                            <img src="{{ $coverWebp ? $coverPath : $page->ogImageUrl() }}" alt="{{ $page->title }}" width="1200" height="750" fetchpriority="high" decoding="async">
                            @if ($coverWebp)</picture>@endif
                        </figure>
                    @endif
                @endif
            @endif
            <?php
                $allModules = $page->modules_list;
                $isBanner = fn ($module) => $module->module_template->code === 'cta-row';
                $bannerHtml = $allModules->filter($isBanner)->map(fn ($module) => $module->draw())->implode("\n");
                $afterModules = $allModules->reject($isBanner);
                $prose = $page->content ? \App\Support\SafeHtml::clean($page->content) : '';
                $faqAccordion = [];
                $hasFaqModule = $allModules->contains(fn ($module) => $module->module_template->code === 'faqs');
                $faqHeading = preg_quote(__('site.faq_heading'), '~');
                if (! $hasFaqModule && preg_match('~<h2[^>]*>\s*'.$faqHeading.'\s*</h2>(.*?)(?=<h2|$)~su', $prose, $faqMatch)) {
                    preg_match_all('~<h3[^>]*>(.*?)</h3>(.*?)(?=<h3|$)~su', $faqMatch[1], $faqPairs, PREG_SET_ORDER);
                    foreach ($faqPairs as $pair) {
                        $faqAccordion[] = ['question' => trim(html_entity_decode(strip_tags($pair[1]), ENT_QUOTES | ENT_HTML5)), 'answer' => trim($pair[2])];
                    }
                    if (count($faqAccordion) >= 2) {
                        $prose = preg_replace('~<h2[^>]*>\s*'.$faqHeading.'\s*</h2>.*?(?=<h2|$)~su', '', $prose, 1);
                    } else {
                        $faqAccordion = [];
                    }
                }
                $proseHead = $prose;
                $proseTail = '';
                if (trim($bannerHtml) !== '' && $prose !== '') {
                    preg_match_all('~<h2[^>]*>(.*?)</h2>~su', $prose, $headings, PREG_OFFSET_CAPTURE);
                    $isFaq = fn ($heading) => trim(strip_tags($heading)) === __('site.faq_heading');
                    $body = array_values(array_filter(array_keys($headings[0]), fn ($i) => ! $isFaq($headings[1][$i][0])));
                    $faq = array_values(array_filter(array_keys($headings[0]), fn ($i) => $isFaq($headings[1][$i][0])));
                    $at = count($body) >= 2 ? $headings[0][$body[intdiv(count($body), 2)]][1] : ($faq ? $headings[0][$faq[0]][1] : null);
                    if ($at !== null) {
                        $proseHead = substr($prose, 0, $at);
                        $proseTail = substr($prose, $at);
                    }
                }
            ?>
            @if ($prose !== '')
                <div class="prose">{!! $proseHead !!}</div>
            @endif
            {!! $bannerHtml !!}
            @if ($proseTail !== '')
                <div class="prose prose-cont">{!! $proseTail !!}</div>
            @endif
            </div>

            @if ($showSidebar)
                <aside class="read-also page-aside" aria-label="{{ __('site.page_aside.label') }}">
                    <h2>{{ __('site.page_aside.try') }}</h2>
                    <div class="ra-card aside-cta">
                        <strong>{{ __('site.page_aside.try_title') }}</strong>
                        <a class="btn" href="{{ route('home') }}#contact">{{ __('site.page_aside.try_button') }} <svg class="arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
                    </div>
                    @if ($usefulPages->isNotEmpty())
                        <h2>{{ __('site.page_aside.useful') }}</h2>
                        @foreach ($usefulPages as $useful)
                            @include('partials.aside-link', ['item' => $useful])
                        @endforeach
                    @endif
                </aside>
            @endif

            @if ($related->isNotEmpty())
                <aside class="read-also page-aside" aria-label="{{ __('site.read_also') }}">
                    <h2>{{ __('site.read_also') }}</h2>
                    @foreach ($related as $item)
                        @include('partials.aside-link', ['item' => $item])
                    @endforeach
                </aside>
            @endif
        </div>
    </article>

    @if ($faqAccordion !== [])
        @include('partials.faq-accordion', ['items' => $faqAccordion])
    @endif
    {!! $afterModules->map(fn ($module) => $module->draw())->implode("\n") !!}
@endsection
