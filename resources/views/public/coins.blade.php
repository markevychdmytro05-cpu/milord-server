@extends('layouts.public')

@php($pageNumber = $coins->currentPage())
@section('title', $pageNumber > 1 ? __('coins.list.title_page', ['page' => $pageNumber, 'last' => $coins->lastPage(), 'brand' => \App\Models\SiteSetting::current()->header_logo]) : __('coins.list.title', ['brand' => \App\Models\SiteSetting::current()->header_logo]))
@section('description', $pageNumber > 1 ? __('coins.list.description_page', ['page' => $pageNumber]) : __('coins.list.description'))
@section('canonical', route('coins.index').(request()->integer('page') > 1 ? '?page='.request()->integer('page') : ''))
@section('robots', $sort === 'desc' || $month || $coins->total() === 0 ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1')

@push('head')
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => __('site.breadcrumbs.home'), 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => __('coins.breadcrumb'), 'item' => route('coins.index')],
    ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <section class="section kb kb--page">
        <div class="wrap">
            <nav class="crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <ol>
                    <li><a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a></li>
                    <li aria-current="page">{{ __('coins.breadcrumb') }}</li>
                </ol>
            </nav>
            <div class="kb-top">
                <div>
                    <h1 class="display">{{ __('coins.list.heading') }}</h1>
                    <p class="kb-lead">{{ __('coins.list.lead') }}</p>
                </div>
                <nav class="kb-filters" aria-label="{{ __('coins.list.sort_label') }}">
                    <a href="{{ route('coins.index', array_filter(['month' => $month])) }}" class="{{ $sort === 'asc' ? 'is-active' : '' }}" @if ($sort === 'asc') aria-current="true" @endif>{{ __('coins.list.sort_asc') }}</a>
                    <a href="{{ route('coins.index', array_filter(['sort' => 'desc', 'month' => $month])) }}" class="{{ $sort === 'desc' ? 'is-active' : '' }}" @if ($sort === 'desc') aria-current="true" @endif rel="nofollow">{{ __('coins.list.sort_desc') }}</a>
                </nav>
            </div>
            @if ($months->count() > 1)
                <nav class="kb-filters kb-filters--months" aria-label="{{ __('coins.list.month_label') }}">
                    <a href="{{ route('coins.index', array_filter(['sort' => $sort === 'desc' ? 'desc' : null])) }}" class="{{ $month ? '' : 'is-active' }}" @if (! $month) aria-current="true" @endif>{{ __('coins.list.month_all') }}</a>
                    @foreach ($months as $key => $label)
                        <a href="{{ route('coins.index', array_filter(['month' => $key, 'sort' => $sort === 'desc' ? 'desc' : null])) }}" class="{{ $month === $key ? 'is-active' : '' }}" @if ($month === $key) aria-current="true" @endif rel="nofollow">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($coins->isEmpty())
                <p class="empty">{{ __('coins.list.empty') }}</p>
            @else
                <div class="kb-grid">
                    @foreach ($coins as $coin)
                        <a class="kb-card" href="{{ $coin->url() }}">
                            @if ($coin->image)
                                <span class="kb-cover kb-cover--coin"><img src="{{ $coin->image }}" alt="{{ __('coins.card.alt', ['name' => $coin->shortTitle()]) }}" @if ($loop->first) fetchpriority="high" @elseif ($loop->index > 2) loading="lazy" @endif></span>
                            @endif
                            <span class="kb-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $coin->releaseLabel() ?? __('coins.card.date_pending') }}</span>
                            @if ($coin->series)
                                <span class="kb-tags"><span class="kb-tag kb-c-{{ crc32($coin->series) % \App\Models\Page::CATEGORY_COLORS }}">{{ $coin->series }}</span></span>
                            @endif
                            <h2>{{ $coin->shortTitle() }}</h2>
                            <p>
                                {{ collect([$coin->denomination, $coin->mintage ? __('coins.card.pcs', ['count' => number_format($coin->mintage, 0, ',', ' ')]) : null, $coin->price ? __('coins.card.price', ['price' => number_format((float) $coin->price, 0, ',', ' ')]) : null])->filter()->implode(' · ') }}
                            </p>
                        </a>
                    @endforeach
                </div>
                {{ $coins->links('vendor.pagination.kb') }}
            @endif
        </div>
    </section>
@endsection
