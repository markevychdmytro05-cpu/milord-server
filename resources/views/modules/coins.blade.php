@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section kb" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        @if (! empty($text['title']))
            <div class="kb-top">
                <div>
                    <h2 class="display">{{ $text['title'] }}</h2>
                    @if (! empty($text['description']))<p class="kb-lead">{{ $text['description'] }}</p>@endif
                </div>
            </div>
        @endif

        <div class="kb-grid">
            @foreach ($coins as $coin)
                <a class="kb-card" href="{{ $coin->url() }}">
                    <span class="kb-cover">
                        @if ($coin->image)
                            <img src="{{ $coin->image }}" alt="{{ __('coins.card.alt', ['name' => $coin->shortTitle()]) }}" loading="lazy">
                        @else
                            @include('partials.kb-cover-fallback', ['icon' => 'coin'])
                        @endif
                    </span>
                    <span class="kb-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $coin->releaseLabel() ?? __('coins.card.date_pending') }}</span>
                    @if ($coin->series)
                        <span class="kb-tags"><span class="kb-tag kb-c-{{ crc32($coin->series) % \App\Models\Page::CATEGORY_COLORS }}">{{ $coin->series }}</span></span>
                    @endif
                    <h3>{{ $coin->shortTitle() }}</h3>
                    <p>{{ collect([$coin->denomination, $coin->mintage ? __('coins.card.pcs', ['count' => number_format($coin->mintage, 0, ',', ' ')]) : null])->filter()->implode(' · ') }}</p>
                </a>
            @endforeach
        </div>

        <div class="kb-pagination">
            <a class="btn outline" href="{{ route('coins.index') }}">{{ ($text['button_text'] ?? '') ?: __('coins.module.all') }} <svg class="arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
        </div>
    </div>
</section>
