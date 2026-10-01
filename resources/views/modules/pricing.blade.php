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
        @if ($packages->isEmpty())
            <p class="empty">{{ $text['empty_text'] ?? '' }}</p>
        @else
            @php($isSlider = $packages->count() > 4)
            <div class="{{ $isSlider ? 'plans-slider' : '' }}" @if ($isSlider) data-plans-slider @endif>
            @if ($isSlider)
                <div class="plans-controls">
                    <button type="button" class="plans-arrow" data-dir="-1" aria-label="{{ __('site.pricing.prev') }}"><svg viewBox="0 0 16 16"><path d="M10 3L5 8l5 5"/></svg></button>
                    <button type="button" class="plans-arrow" data-dir="1" aria-label="{{ __('site.pricing.next') }}"><svg viewBox="0 0 16 16"><path d="M6 3l5 5-5 5"/></svg></button>
                </div>
            @endif
            <div class="plans {{ $isSlider ? 'plans--track' : '' }}">
                @foreach ($packages as $package)
                    <div class="plan {{ $package->isTrial() ? 'plan--trial' : '' }}">
                        <h3>{{ $package->name }}</h3>
                        @if ($package->isTrial())
                            <span class="plan-badge">{{ __('site.pricing.badge_free') }}</span>
                            <div class="price price--free">{{ __('site.pricing.free') }}</div>
                        @else
                            <div class="price"><sup>$</sup>{{ $package->price }}</div>
                        @endif
                        <ul class="facts">
                            <li><span>{{ __('site.pricing.accounts') }}</span><b>{{ $package->max_accounts }}</b></li>
                            <li><span>{{ __('site.pricing.devices') }}</span><b>{{ $package->max_devices }}</b></li>
                            <li><span>{{ __('site.pricing.term') }}</span><b>{{ $package->durationLabel() }}</b></li>
                        </ul>
                        @php($buttonText = $package->isTrial() ? ($text['trial_text'] ?? __('site.pricing.trial_button')) : ($text['order_text'] ?? ''))
                        @if ($buttonText !== '')
                            <a class="btn outline" href="{{ $package->isTrial() || ! $contactUrl ? '#contact' : $contactUrl }}" @if (! $package->isTrial() && $contactUrl) target="_blank" rel="noopener" @endif>{{ $buttonText }} <svg class="arrow" viewBox="0 0 16 16"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
                        @endif
                    </div>
                @endforeach
            </div>
            </div>
            @if ($isSlider)
                <script>
                    (function () {
                        var root = document.querySelector('[data-plans-slider]');
                        if (!root) { return; }
                        var track = root.querySelector('.plans--track');
                        var buttons = root.querySelectorAll('.plans-arrow');
                        function step() { var card = track.querySelector('.plan'); return card ? card.offsetWidth + 20 : 300; }
                        function update() {
                            buttons[0].disabled = track.scrollLeft <= 2;
                            buttons[1].disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
                        }
                        buttons.forEach(function (button) {
                            button.addEventListener('click', function () { track.scrollBy({ left: step() * Number(button.dataset.dir), behavior: 'smooth' }); });
                        });
                        track.addEventListener('scroll', update, { passive: true });
                        window.addEventListener('resize', update);
                        update();
                    })();
                </script>
            @endif
        @endif
        @if (! empty($text['note']))
            <p class="note">{{ $text['note'] }}</p>
        @endif
    </div>
</section>
