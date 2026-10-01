@extends('layouts.public')

@php($pageNumber = $coins->currentPage())
@section('title', $pageNumber > 1 ? __('coins.list.title_page', ['page' => $pageNumber, 'last' => $coins->lastPage(), 'brand' => \App\Models\SiteSetting::current()->header_logo]) : __('coins.list.title', ['brand' => \App\Models\SiteSetting::current()->header_logo]))
@section('description', $pageNumber > 1 ? __('coins.list.description_page', ['page' => $pageNumber]) : __('coins.list.description'))
@section('canonical', route('coins.index').(request()->integer('page') > 1 ? '?page='.request()->integer('page') : ''))
@section('robots', $sort === 'desc' ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1')

@section('content')
    <section class="section kb">
        <div class="wrap">
            <nav class="crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a>
                <span>{{ __('coins.breadcrumb') }}</span>
            </nav>
            <div class="kb-top">
                <div>
                    <h1 class="display">{{ __('coins.list.heading') }}</h1>
                    <p class="kb-lead">{{ __('coins.list.lead') }}</p>
                </div>
                <nav class="kb-filters" aria-label="{{ __('coins.list.sort_label') }}">
                    <a href="{{ route('coins.index') }}" class="{{ $sort === 'asc' ? 'is-active' : '' }}">{{ __('coins.list.sort_asc') }}</a>
                    <a href="{{ route('coins.index', ['sort' => 'desc']) }}" class="{{ $sort === 'desc' ? 'is-active' : '' }}" rel="nofollow">{{ __('coins.list.sort_desc') }}</a>
                </nav>
            </div>

            @if ($coins->isEmpty())
                <p class="empty">{{ __('coins.list.empty') }}</p>
            @else
                <div class="kb-grid">
                    @foreach ($coins as $coin)
                        <a class="kb-card" href="{{ $coin->url() }}">
                            <span class="kb-cover">
                                @if ($coin->image)
                                    <img src="{{ $coin->image }}" alt="{{ __('coins.card.alt', ['name' => $coin->shortTitle()]) }}" loading="lazy">
                                @else
                                    @include('partials.kb-cover-fallback', ['icon' => 'coin'])
                                @endif
                            </span>
                            <span class="kb-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $coin->releaseLabel() ?? __('coins.card.date_pending') }}</span>
                            @if ($coin->series)
                                <span class="kb-tags"><span class="kb-tag kb-c-{{ crc32($coin->series) % \App\Models\Page::CATEGORY_COLORS }}">{{ $coin->series }}</span></span>
                            @endif
                            <h3>{{ $coin->shortTitle() }}</h3>
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
