@php($locale = \App\Support\SiteLocale::current())
<div class="main-banner__block">
    <div class="main-banner__box">
        <h2 class="main-title">{{ $settings[$locale]['title'] ?? '' }}</h2>
        <div class="main-banner__content">
            {!! \App\Support\SafeHtml::clean($settings[$locale]['description'] ?? '') !!}
        </div>
        @if (! empty($settings[$locale]['url']))
            <div class="main-banner__content-buttons">
                <a class="btn" href="{{ $settings[$locale]['url'] }}">{{ ($settings[$locale]['text_url'] ?? '') ?: __('site.more') }}</a>
            </div>
        @endif
    </div>
</div>
