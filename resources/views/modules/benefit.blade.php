@php($locale = \App\Support\SiteLocale::current())
@inject('moduleFiles', 'App\Services\ModuleService')
<div class="benefit-section _bg-{{ $settings['type'] ?? 'gray' }} {{ ! empty($settings['add_padding']) ? 'pt-md' : 'pb-lg' }}">
    <div class="container">
        <div class="benefit-section__block-row {{ ($settings['align'] ?? 'left') === 'left' ? 'flex-row' : 'flex-row-reverse' }}">
            <div class="benefit-section__block-col _col-content" data-aos="fade-{{ ($settings['align'] ?? 'left') === 'left' ? 'right' : 'left' }}">
                @if (! empty($settings[$locale]['title']))
                    <h2 class="section-title benefit-section__title">
                        <b>{{ $settings[$locale]['title'] }}</b>
                    </h2>
                @endif
                <div class="section-content benefit-section__content js-content-wrapper content-multiline">
                    <div class="js-content-inner content-multiline__inner crm-overview__item-content-inner">
                        {!! \App\Support\SafeHtml::clean($settings[$locale]['description'] ?? '') !!}
                    </div>
                </div>
                @if (! empty($settings['show_button']) && ! empty($settings['button_url']))
                    <a class="btn btn-primary" href="{{ $settings['button_url'] }}">{{ ($settings[$locale]['button_text'] ?? '') ?: __('site.more') }}</a>
                @endif
            </div>
            @if (! empty($settings['image']))
                <div class="benefit-section__block-col _col-picture" data-aos="fade-{{ ($settings['align'] ?? 'left') === 'left' ? 'left' : 'right' }}">
                    <div class="benefit-section__picture">
                        <picture class="benefit-section__picture__image">
                            <img class="benefit-section__picture__img" src="{{ $moduleFiles->url($settings['image']) }}" alt="{{ $settings[$locale]['title'] ?? '' }}" loading="lazy">
                        </picture>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
