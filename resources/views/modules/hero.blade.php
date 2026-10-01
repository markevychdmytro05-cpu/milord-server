@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
@inject('moduleFiles', 'App\Services\ModuleService')
@php($points = array_filter(array_map('trim', preg_split('/\R/u', (string) ($text['points'] ?? '')))))
@php($hasImage = ! empty($settings['image']))
@php($imageUrl = $hasImage ? $moduleFiles->url($settings['image']) : '')
@php($imageFile = str_starts_with($imageUrl, '/') ? public_path(ltrim($imageUrl, '/')) : null)
@php($webpUrl = $imageFile && is_file(preg_replace('/\.(png|jpe?g)$/i', '.webp', $imageFile)) ? preg_replace('/\.(png|jpe?g)$/i', '.webp', $imageUrl) : null)
@php($srcset = $webpUrl ? collect([640, 960])->filter(fn ($w) => is_file(public_path(ltrim(preg_replace('/\.webp$/', "-$w.webp", $webpUrl), '/'))))
    ->map(fn ($w) => preg_replace('/\.webp$/', "-$w.webp", $webpUrl)." {$w}w")->push($webpUrl.' 1440w')->implode(', ') : null)
@php($imageSize = $imageFile && is_file($imageFile) ? getimagesize($imageFile) : false)
<header class="hero grid-bg">
    <div class="wrap {{ $hasImage ? '' : 'hero-single' }}">
        <div>
            <h1 class="display">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2']))<br><em>{{ $text['title_2'] }}</em>@endif</h1>
            @if (! empty($text['lead']))
                <p class="lead">{{ $text['lead'] }}</p>
            @endif
            @if ($points)
                <ul class="hero-points">
                    @foreach ($points as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="actions">
                @if (! empty($text['button_text']) && ! empty($settings['button_url']))
                    <a class="btn" href="{{ $settings['button_url'] }}">{{ $text['button_text'] }} <svg class="arrow" viewBox="0 0 16 16"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
                @endif
                @if (! empty($text['button2_text']) && ! empty($settings['button2_url']))
                    <a class="btn ghost" href="{{ $settings['button2_url'] }}">{{ $text['button2_text'] }}</a>
                @endif
            </div>
        </div>
        @if ($hasImage)
            <div class="hero-visual">
                <picture>
                    @if ($webpUrl)<source srcset="{{ $srcset }}" sizes="(max-width: 1000px) 100vw, 620px" type="image/webp">@endif
                    <img src="{{ $imageUrl }}" alt="{{ $text['image_alt'] ?? $text['title_1'] ?? '' }}" @if ($imageSize) width="{{ $imageSize[0] }}" height="{{ $imageSize[1] }}" @endif fetchpriority="high" decoding="async">
                </picture>
            </div>
        @endif
    </div>
</header>
