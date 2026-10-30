@php($faqId = 'faq-'.substr(md5(json_encode($items)), 0, 6))
<div class="faq">
    <div class="container">
        <h2 class="section-title">{{ $title ?? __('site.faq_heading') }}</h2>
        <div class="accordion">
            @foreach ($items as $item)
                <div class="accordion__item">
                    <button class="accordion__toggler" type="button" aria-expanded="false" aria-controls="{{ $faqId.'-'.$loop->index }}">
                        <span class="accordion__toggler-name">{{ $item['question'] }}</span><span class="accordion__toggler-icon" aria-hidden="true"></span>
                    </button>
                    <div class="accordion__list" id="{{ $faqId.'-'.$loop->index }}">
                        <div>{!! $item['answer'] !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
