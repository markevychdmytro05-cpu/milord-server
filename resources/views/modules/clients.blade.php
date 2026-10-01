@php($locale = \App\Support\SiteLocale::current())
@inject('moduleFiles', 'App\Services\ModuleService')
<section class="our-clients">
    <div class="container">
        @if (! empty($settings[$locale]['title']))
            <h2 class="section-title our-clients__title" data-aos="zoom-in">{{ $settings[$locale]['title'] }}</h2>
        @endif

        <div class="our-clients__row row">
            @foreach ($settings['items'] ?? [] as $item)
                <div class="our-clients__col" data-aos="fade-in" data-aos-delay="100">
                    <a href="{{ $item['url'] }}" target="_blank" class="our-clients__card" rel="noopener noreferrer" title="{{ $item['url'] }}">
                        <img class="our-clients__card" src="{{ $moduleFiles->url($item['image']) }}" alt="{{ $item['url'] }}" width="236" height="70" loading="lazy">
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
