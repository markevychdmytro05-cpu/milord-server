@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section stats-section" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        @if (! empty($text['title']) || ! empty($text['description']))
            <div class="head">
                <div>
                    @if (! empty($text['title']))<h2 class="display">{{ $text['title'] }}</h2>@endif
                </div>
                @if (! empty($text['description']))<p>{{ $text['description'] }}</p>@endif
            </div>
        @endif
        <dl class="stats">
            @foreach ($settings['items'] ?? [] as $item)
                <div class="stat">
                    <dt>{{ $item['label'] ?? '' }}</dt>
                    <dd>
                        <b>{{ $item['value'] ?? '' }}</b>@if (! empty($item['unit']))<span class="stat-unit">{{ $item['unit'] }}</span>@endif
                    </dd>
                    @if (! empty($item['note']))<p>{{ $item['note'] }}</p>@endif
                </div>
            @endforeach
        </dl>
    </div>
</section>
