@extends('layouts.public')

@section('title', $topic->title.' | '.__('community.heading'))
@section('description', $locked ? __('community.locked_text') : \Illuminate\Support\Str::limit(strip_tags($topic->body), 150))
@section('canonical', $topic->url())
@section('robots', 'noindex, follow')

@section('content')
    @php($initials = \Illuminate\Support\Str::upper(collect(explode(' ', trim($topic->author_name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')))
    <section class="section kb cm-page">
        <div class="wrap cm-wrap">
            <nav class="crumbs cm-crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <ol>
                    <li><a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a></li>
                    <li><a href="{{ route('community.index') }}">{{ __('community.breadcrumb') }}</a></li>
                    <li><a href="{{ route('community.index', ['type' => $topic->type]) }}">{{ __('community.types.'.$topic->type) }}</a></li>
                    <li aria-current="page">{{ $topic->title }}</li>
                </ol>
            </nav>

            @include('community._badges')
            <h1 class="display cm-h1">{{ $topic->title }}</h1>

            <div class="cm-author">
                <span class="cm-avatar">{{ $initials }}</span>
                <div>
                    <strong>{{ $topic->author_name }}</strong>
                    <p class="cm-meta cm-meta-line">
                        <span>{{ $topic->is_staff ? __('community.role_staff') : __('community.role_member') }}</span>
                        <span>{{ $topic->published_at?->translatedFormat('d.m.Y') }}</span>
                        <span title="{{ __('community.stats.views') }}">@include('community._icon', ['name' => 'eye']){{ $topic->views }}</span>
                        <span title="{{ __('community.stats.comments') }}">@include('community._icon', ['name' => 'comment']){{ $topic->comments_count }}</span>
                        @if ($topic->type === 'review' && $topic->rating)<span>@include('community._icon', ['name' => 'review']){{ $topic->rating }}/5</span>@endif
                    </p>
                </div>
            </div>

            @if ($locked)
                <div class="cm-lock">
                    @include('community._icon', ['name' => 'lock'])
                    <h2>{{ __('community.lock.heading') }}</h2>
                    <p>{{ __('community.lock.text') }}</p>
                    <button class="btn" type="button" data-cm-open="cm-key-dialog">{{ __('community.lock.button') }}</button>
                    @if ($topic->comments_count > 0)<p class="cm-meta">{{ __('community.lock.hidden_comments', ['count' => $topic->comments_count]) }}</p>@endif
                </div>
            @else
                <div class="prose cm-body">{!! nl2br(e($topic->body)) !!}</div>
            @endif

            <div class="cm-actions">
                <form method="post" action="{{ route('community.like', $topic->slug) }}">
                    @csrf
                    <button class="cm-action {{ $liked ? 'is-on' : '' }}" type="submit" aria-pressed="{{ $liked ? 'true' : 'false' }}" title="{{ __('community.actions.like') }}">@include('community._icon', ['name' => 'heart'])<span>{{ $topic->likes_count }}</span></button>
                </form>
                <button class="cm-action" type="button" data-cm-share="{{ $topic->url() }}" data-cm-copied="{{ __('community.actions.copied') }}">@include('community._icon', ['name' => 'share'])<span>{{ __('community.actions.share') }}</span></button>
                @if ($license)
                    <button class="cm-action" type="button" data-cm-open="cm-report-dialog">@include('community._icon', ['name' => 'flag'])<span>{{ __('community.actions.report') }}</span></button>
                @endif
            </div>

            @unless ($locked)
                <h2 class="cm-subhead">{{ __('community.comment.heading') }}</h2>
                @forelse ($comments as $comment)
                    <article class="cm-comment">
                        <p class="cm-meta"><strong>{{ $comment->author_name }}</strong>@if ($comment->is_staff) <span class="cm-badge cm-badge--staff">{{ __('community.staff') }}</span>@endif · {{ $comment->created_at->translatedFormat('d.m.Y H:i') }}</p>
                        <p>{!! nl2br(e($comment->body)) !!}</p>
                    </article>
                @empty
                    <p class="cm-meta">{{ __('community.comment.none') }}</p>
                @endforelse

                @if ($license)
                    <form class="cm-form" method="post" action="{{ route('community.comment', $topic->slug) }}">
                        @csrf
                        <div class="cm-field">
                            <label for="comment-body">{{ __('community.form.comment') }}</label>
                            <textarea id="comment-body" name="body" rows="4" maxlength="2000" required>{{ old('body') }}</textarea>
                            @error('body')<p class="cm-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="cm-field cm-field--half">
                            <label for="comment-name">{{ __('community.form.name') }}</label>
                            <input id="comment-name" name="name" type="text" maxlength="100" value="{{ old('name') }}">
                        </div>
                        <div class="cm-trap" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                        <p class="cm-meta">{{ __('community.topic.moderation_note') }}</p>
                        <button class="btn" type="submit">{{ __('community.comment.submit') }}</button>
                    </form>
                @endif
            @endunless
        </div>
    </section>

    @unless ($license)
        @include('community._key')
    @endunless
    @if ($license)
        <dialog class="cm-dialog" id="cm-report-dialog">
            <form method="post" action="{{ route('community.report', $topic->slug) }}">
                @csrf
                <button class="cm-dialog-close" type="button" data-cm-close aria-label="{{ __('community.close') }}">&times;</button>
                <h2>{{ __('community.report.heading') }}</h2>
                <p>{{ __('community.report.text') }}</p>
                <div class="cm-field">
                    <label for="report-reason">{{ __('community.report.reason') }}</label>
                    <textarea id="report-reason" name="reason" rows="3" maxlength="500"></textarea>
                </div>
                <button class="btn" type="submit">{{ __('community.report.submit') }}</button>
            </form>
        </dialog>
    @endif
    @include('community._scripts')
@endsection
