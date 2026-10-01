@php($locale = \App\Support\SiteLocale::current())
<div class="answers__block aos-init aos-animate" data-aos="fade-{{ $fade }}">
    <div class="section-content">
        <h2>{{ $settings[$locale]['title'] ?? '' }}</h2>
        <div>
            {!! \App\Support\SafeHtml::clean($settings[$locale]['description'] ?? '') !!}
        </div>
        @if (! empty($settings[$locale]['url']))
            <a class="btn btn-primary" href="{{ $settings[$locale]['url'] }}">{{ ($settings[$locale]['text_url'] ?? '') ?: __('site.more') }}</a>
        @endif
    </div>
</div>
