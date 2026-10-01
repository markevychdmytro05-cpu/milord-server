@php
    $locale = \App\Support\SiteLocale::current();
    $items = $settings['items'] ?? [];
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn (array $item): array => [
            '@type' => 'Question',
            'name' => $item['question'] ?? '',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['description'] ?? ''],
        ], $items),
    ];
@endphp
<div class="faq">
    <div class="container">
        @if (! empty($settings[$locale]['title']))
            <h2 class="section-title" data-aos="zoom-in">{{ $settings[$locale]['title'] }}</h2>
        @endif
        <div class="accordion">
            @foreach ($items as $item)
                <div class="accordion__item td-{{ $loop->index * 100 }}" data-aos="zoom-in" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <button class="accordion__toggler" type="button" aria-expanded="false">
                        <span class="accordion__toggler-name" itemprop="name">{{ $item['question'] ?? '' }}</span><span class="accordion__toggler-icon"></span>
                    </button>
                    <div class="accordion__list" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <div itemprop="text">{!! nl2br(e($item['description'] ?? '')) !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
