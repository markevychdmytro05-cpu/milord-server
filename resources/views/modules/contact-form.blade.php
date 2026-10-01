@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section grid-bg contact-section" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <div class="head">
            <div>
                <h2 class="display">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2']))<br><em>{{ $text['title_2'] }}</em>@endif</h2>
            </div>
            @if (! empty($text['description']))<p>{{ $text['description'] }}</p>@endif
        </div>

        <div class="contact-form-wrap" id="contact-form">
            <form class="contact-form" method="post" action="{{ route('contact.store') }}" data-success="{{ $text['success_text'] ?? '' }}">
                @csrf
                <input type="hidden" name="source" value="{{ url()->current() }}">
                <div class="contact-hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

                <label class="contact-field">
                    <span>{{ $text['name_label'] ?? '' }}</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="100" required>
                    @error('name')<em class="contact-error">{{ $message }}</em>@enderror
                </label>
                <label class="contact-field">
                    <span>{{ $text['contact_label'] ?? '' }}</span>
                    <input type="text" name="contact" value="{{ old('contact') }}" maxlength="150" required>
                    @error('contact')<em class="contact-error">{{ $message }}</em>@enderror
                </label>
                @if (! empty($text['message_label']))
                    <label class="contact-field contact-field-wide">
                        <span>{{ $text['message_label'] }}</span>
                        <textarea name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
                        @error('message')<em class="contact-error">{{ $message }}</em>@enderror
                    </label>
                @endif
                <button class="btn" type="submit">{{ $text['button_text'] ?? '' }} <svg class="arrow" viewBox="0 0 16 16"><path d="M2 8h12M9 3l5 5-5 5"/></svg></button>
            </form>
        </div>
    </div>
</section>
