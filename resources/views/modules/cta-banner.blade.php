@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="cta dark" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <h2 class="display" style="margin-top:18px">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2']))<br><em>{{ $text['title_2'] }}</em>@endif</h2>
        <div class="actions">
            <a class="btn inv" href="{{ $url }}" @if ($external) target="_blank" rel="noopener" @endif>{{ $text['button_text'] ?? '' }} <svg class="arrow" viewBox="0 0 16 16"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
        </div>
    </div>
</section>
