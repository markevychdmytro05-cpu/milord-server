@php($locale = \App\Support\SiteLocale::current())
<section class="cta-row @if (! empty($settings['bg_gray'])) section_bg_gray @endif">
    <div class="container">
        <div class="cta-row__wrap">
            <div class="cta-row__content">
                <h3 class="cta-row__title">{{ $settings[$locale]['title'] ?? '' }}</h3>
                <p class="cta-row__text">{{ $settings[$locale]['text'] ?? '' }}</p>
            </div>
            <div class="cta-row__btn">
                <a class="btn btn_pimary btn_request" href="{{ $settings['url'] ?? '#' }}">
                    {{ $settings[$locale]['button_text'] ?? '' }}
                </a>
            </div>
        </div>
    </div>
</section>
