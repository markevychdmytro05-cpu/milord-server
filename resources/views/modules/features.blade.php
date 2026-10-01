@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section grid-bg" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <div class="head">
                <div>
                    <h2 class="display">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2']))<br><em>{{ $text['title_2'] }}</em>@endif</h2>
                </div>
                @if (! empty($text['description']))<p>{{ $text['description'] }}</p>@endif
            </div>
        <div class="cards">
            @foreach ($settings['items'] ?? [] as $item)
                <div class="card">
                    <svg viewBox="0 0 24 24">{!! \App\Support\ModuleIcons::svg($item['icon'] ?? null) !!}</svg>
                    <h3>{{ $item['title'] ?? '' }}</h3>
                    <p>{{ $item['text'] ?? '' }}</p>
                    @php($points = array_filter(array_map('trim', preg_split('/\R/u', (string) ($item['points'] ?? '')))))
                    @if ($points)
                        <ul>
                            @foreach ($points as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
