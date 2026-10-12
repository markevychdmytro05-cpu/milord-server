@extends('layouts.public')

@section('title', __('community.title'))
@section('description', __('community.description'))
@section('canonical', route('community.index'))
@section('robots', ($type || $search !== '' || $sort !== 'new' || $topics->currentPage() > 1) ? 'noindex, follow' : 'index, follow')

@section('content')
    <section class="section kb cm-page">
        <div class="wrap">
            <nav class="crumbs cm-crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a>
                <span>{{ __('community.breadcrumb') }}</span>
            </nav>

            <div class="cm-head">
                <div>
                    <h1 class="display cm-h1">{{ $type ? __('community.types.'.$type) : __('community.heading') }}</h1>
                    <p class="kb-lead">{{ __('community.lead') }}</p>
                </div>
                @if ($license)
                    <div class="cm-session">
                        <span>{{ __('community.key.signed_in', ['name' => explode(' ', trim($license->customer))[0]]) }}</span>
                        <a class="btn" href="{{ route('community.create') }}">{{ __('community.topic.new') }}</a>
                        <form method="post" action="{{ route('community.leave') }}">@csrf<button class="cm-link" type="submit">{{ __('community.key.leave') }}</button></form>
                    </div>
                @endif
            </div>

            @if ($license)
            <form class="cm-toolbar" method="get" action="{{ route('community.index') }}">
                <label class="cm-toolbar-search">
                    <span class="visually-hidden">{{ __('community.search.placeholder') }}</span>
                    <input name="q" type="search" value="{{ $search }}" placeholder="{{ __('community.search.placeholder') }}" maxlength="100">
                </label>
                <label>
                    <span class="visually-hidden">{{ __('community.form.type') }}</span>
                    <select name="type">
                        <option value="">{{ __('community.all_types') }}</option>
                        @foreach (\App\Models\CommunityTopic::TYPES as $filter)
                            <option value="{{ $filter }}" @selected($type === $filter)>{{ __('community.types.'.$filter) }} ({{ $counts[$filter] ?? 0 }})</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="visually-hidden">{{ __('community.sort.label') }}</span>
                    <select name="sort">
                        @foreach (['new', 'popular', 'discussed'] as $option)
                            <option value="{{ $option }}" @selected($sort === $option)>{{ __('community.sort.'.$option) }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn" type="submit">{{ __('community.search.button') }}</button>
            </form>
            @else
                <div class="cm-empty">
                    @include('community._icon', ['name' => 'lock'])
                    <h2>{{ __('community.lock.heading') }}</h2>
                    <p>{{ __('community.lead_locked') }}</p>
                    <button class="btn" type="button" data-cm-open="cm-key-dialog">{{ __('community.lock.button') }}</button>
                </div>
            @endif

            @if ($topics->isEmpty())
                @if ($license)
                <div class="cm-empty">
                    @include('community._icon', ['name' => 'discussion'])
                    <p>{{ __('community.empty') }}</p>
                </div>
                @endif
            @else
                <ul class="cm-list">
                    @foreach ($topics as $topic)
                        <li class="cm-item">
                            <span class="cm-typebox cm-typebox--{{ $topic->type }}">@include('community._icon', ['name' => $topic->type])</span>
                            <div class="cm-item-main">
                                <div class="cm-badges">
                                    <span class="cm-badge cm-badge--{{ $topic->type }}">@include('community._icon', ['name' => $topic->type]){{ __('community.type_singular.'.$topic->type) }}</span>
                                    @if ($topic->is_pinned)<span class="cm-badge">{{ __('community.pinned') }}</span>@endif
                                    @if ($topic->is_members_only)<span class="cm-badge">@include('community._icon', ['name' => 'lock']){{ __('community.members') }}</span>@endif
                                    @if ($topic->is_staff)<span class="cm-badge cm-badge--staff">{{ __('community.staff') }}</span>@endif
                                </div>
                                <a class="cm-title" href="{{ $topic->url() }}">{{ $topic->title }}</a>
                                @if ($topic->is_members_only && ! $license)<p class="cm-locked">{{ __('community.locked_text') }}</p>@endif
                                <p class="cm-meta">{{ $topic->author_name }}</p>
                            </div>
                            <div class="cm-item-side">
                                <p class="cm-counters">
                                    <span title="{{ __('community.stats.comments') }}">@include('community._icon', ['name' => 'comment']){{ $topic->comments_count }}</span>
                                    <span title="{{ __('community.stats.views') }}">@include('community._icon', ['name' => 'eye']){{ $topic->views }}</span>
                                </p>
                                <time class="cm-meta">{{ $topic->published_at?->translatedFormat('d.m.Y') }}</time>
                            </div>
                        </li>
                    @endforeach
                </ul>
                {{ $topics->links('vendor.pagination.kb') }}
            @endif
        </div>
    </section>
    @unless ($license)
        @include('community._key')
    @endunless
    @include('community._scripts')
@endsection
