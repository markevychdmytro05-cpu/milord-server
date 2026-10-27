@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section contact-section" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <div class="contact-orbs" aria-hidden="true"><span class="orb"></span><span class="orb"></span></div>
        <div class="head">
            <h2 class="display">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2']))<br> <em>{{ $text['title_2'] }}</em>@endif</h2>
            @if (! empty($text['description']))<p>{{ $text['description'] }}</p>@endif
        </div>

        <div class="contact-form-wrap" id="contact-form">
            {{-- Повідомлення для JS-відправки: помилки показуються біля полів, підсумок і успіх – у .contact-status. --}}
            <form class="contact-form" method="post" action="{{ route('contact.store') }}" novalidate
                data-success="{{ $text['success_text'] ?? '' }}" data-invalid="{{ __('site.contact.invalid') }}" data-expired="{{ __('site.contact.expired') }}"
                data-too-many="{{ __('site.toast.too_many') }}" data-failed="{{ __('site.toast.failed') }}" data-network="{{ __('site.toast.network') }}"
                data-next-url="{{ route('downloads.index') }}" data-next-text="{{ __('site.contact.next') }}">
                @csrf
                <input type="hidden" name="source" value="{{ url()->current() }}">
                <div class="contact-hp" aria-hidden="true" inert><input type="text" name="nb_hp" tabindex="-1" autocomplete="off"></div>

                <p class="contact-note" aria-hidden="true">{!! __('site.contact.required_note', ['mark' => '<span class="req" aria-hidden="true">*</span>']) !!}</p>
                <div class="contact-field">
                    <label for="contact-name">{{ $text['name_label'] ?? '' }} <span class="req" aria-hidden="true">*</span></label>
                    <input id="contact-name" type="text" name="name" value="{{ old('name') }}" maxlength="100" autocomplete="given-name" required @error('name') aria-invalid="true" aria-describedby="error-name" @enderror>
                    @error('name')<em class="contact-error" id="error-name">{{ $message }}</em>@enderror
                </div>
                <div class="contact-field">
                    <label for="contact-contact">{{ $text['contact_label'] ?? '' }} <span class="req" aria-hidden="true">*</span></label>
                    <input id="contact-contact" type="text" name="contact" value="{{ old('contact') }}" maxlength="150" required autocapitalize="none" spellcheck="false" data-hint-id="contact-hint" aria-describedby="contact-hint @error('contact') error-contact @enderror" @error('contact') aria-invalid="true" @enderror>
                    <small class="contact-hint" id="contact-hint">{{ __('site.contact.contact_hint') }}</small>
                    @error('contact')<em class="contact-error" id="error-contact">{{ $message }}</em>@enderror
                </div>
                @if (! empty($text['message_label']))
                    <div class="contact-field contact-field-wide">
                        <label for="contact-message">{{ $text['message_label'] }} <span class="optional">({{ __('site.contact.optional') }})</span></label>
                        <textarea id="contact-message" name="message" rows="4" maxlength="2000" @error('message') aria-invalid="true" aria-describedby="error-message" @enderror>{{ old('message') }}</textarea>
                        @error('message')<em class="contact-error" id="error-message">{{ $message }}</em>@enderror
                    </div>
                @endif
                <p class="contact-status" hidden></p>
                <button class="btn" type="submit">{{ $text['button_text'] ?? '' }} <svg class="arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg></button>
                <p class="contact-consent">{!! __('site.contact.consent', ['link' => '<a href="'.e(route('pages.show', 'polityka-konfidentsiynosti')).'">'.e(__('site.contact.privacy')).'</a>']) !!}</p>
            </form>
        </div>
    </div>
</section>
