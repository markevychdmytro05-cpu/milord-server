@extends('layouts.public')

@section('title', __('community.topic.create_heading').' | '.__('community.heading'))
@section('description', __('community.description'))
@section('canonical', route('community.create'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="section kb cm-page">
        <div class="wrap cm-wrap">
            <nav class="crumbs cm-crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <ol>
                    <li><a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a></li>
                    <li><a href="{{ route('community.index') }}">{{ __('community.breadcrumb') }}</a></li>
                    <li aria-current="page">{{ __('community.topic.create_heading') }}</li>
                </ol>
            </nav>
            <h1 class="display cm-h1">{{ __('community.topic.create_heading') }}</h1>

            <form class="cm-form" method="post" action="{{ route('community.store') }}">
                @csrf
                <div class="cm-field cm-field--half">
                    <label for="topic-type">{{ __('community.form.type') }}</label>
                    <select id="topic-type" name="type" required>
                        @foreach (\App\Models\CommunityTopic::USER_TYPES as $option)
                            <option value="{{ $option }}" @selected(old('type', $type) === $option)>{{ __('community.type_singular.'.$option) }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="cm-error">{{ $message }}</p>@enderror
                </div>
                <div class="cm-field cm-field--half" id="topic-rating-field">
                    <label for="topic-rating">{{ __('community.form.rating') }}</label>
                    <select id="topic-rating" name="rating">
                        <option value="">{{ __('community.form.rating_choose') }}</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected((int) old('rating') === $i)>{{ $i }} / 5</option>
                        @endfor
                    </select>
                    @error('rating')<p class="cm-error">{{ $message }}</p>@enderror
                </div>
                <div class="cm-field">
                    <label for="topic-title">{{ __('community.form.title') }}</label>
                    <input id="topic-title" name="title" type="text" maxlength="150" value="{{ old('title') }}" required>
                    @error('title')<p class="cm-error">{{ $message }}</p>@enderror
                </div>
                <div class="cm-field">
                    <label for="topic-body">{{ __('community.form.body') }}</label>
                    <textarea id="topic-body" name="body" rows="8" maxlength="5000" required>{{ old('body') }}</textarea>
                    @error('body')<p class="cm-error">{{ $message }}</p>@enderror
                </div>
                <div class="cm-field cm-field--half">
                    <label for="topic-name">{{ __('community.form.name') }}</label>
                    <input id="topic-name" name="name" type="text" maxlength="100" value="{{ old('name') }}">
                </div>
                <div class="cm-trap" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <p class="cm-meta">{{ __('community.topic.moderation_note') }}</p>
                <button class="btn" type="submit">{{ __('community.topic.submit') }}</button>
            </form>
        </div>
    </section>
    <script>
        (function () {
            var type = document.getElementById('topic-type');
            var field = document.getElementById('topic-rating-field');
            function sync() { field.hidden = type.value !== 'review'; }
            type.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
