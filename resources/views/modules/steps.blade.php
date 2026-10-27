@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section steps-section" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <div class="head">
            <div>
                <h2 class="display">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2']))<br> <em>{{ $text['title_2'] }}</em>@endif</h2>
            </div>
            @if (! empty($text['description']))<p>{{ $text['description'] }}</p>@endif
        </div>
        <ol class="steps">
            @foreach ($settings['items'] ?? [] as $item)
                <li class="step">
                    <div class="step-art" aria-hidden="true"><span class="orb"></span><b>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</b></div>
                    <div class="step-body">
                        <h3>{{ $item['title'] ?? '' }}</h3>
                        <p>{{ $item['text'] ?? '' }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
