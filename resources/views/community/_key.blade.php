<dialog class="cm-dialog" id="cm-key-dialog">
    <form method="post" action="{{ route('community.enter') }}">
        @csrf
        <button class="cm-dialog-close" type="button" data-cm-close aria-label="{{ __('community.close') }}">&times;</button>
        <h2>{{ __('community.key.heading') }}</h2>
        <p>{{ __('community.key.text') }}</p>
        <div class="cm-field">
            <label for="community-key">{{ __('community.key.label') }}</label>
            <input id="community-key" name="key" type="text" autofocus value="{{ old('key') }}" placeholder="{{ __('community.key.placeholder') }}" autocomplete="off" required>
            @error('key')<p class="cm-error">{{ $message }}</p>@enderror
        </div>
        <button class="btn" type="submit">{{ __('community.key.submit') }}</button>
    </form>
</dialog>
