@php
    $locale = \App\Support\SiteLocale::current();
    $items = $settings['items'] ?? [];
    $faqId = 'faq-'.substr(md5(json_encode($items)), 0, 6);
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn (array $item): array => [
            '@type' => 'Question',
            'name' => $item['question'] ?? '',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item['description'] ?? '')],
        ], $items),
    ];
@endphp
<div class="faq">
    <div class="container">
        @if (! empty($settings[$locale]['title']))
            <h2 class="section-title">{{ $settings[$locale]['title'] }}</h2>
        @endif
        <div class="accordion">
            @foreach ($items as $item)
                <div class="accordion__item">
                    <button class="accordion__toggler" type="button" aria-expanded="false" aria-controls="{{ $faqId.'-'.$loop->index }}">
                        <span class="accordion__toggler-name">{{ $item['question'] ?? '' }}</span><span class="accordion__toggler-icon" aria-hidden="true"></span>
                    </button>
                    <div class="accordion__list" id="{{ $faqId.'-'.$loop->index }}">
                        <div>{!! nl2br(strip_tags($item['description'] ?? '', '<a>')) !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
