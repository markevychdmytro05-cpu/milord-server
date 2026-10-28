@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
{{-- Безкоштовний пробний тариф – банер зверху, платні – картки під ним. --}}
@php($trials = $packages->filter(fn ($package) => $package->isTrial()))
@php($paid = $packages->reject(fn ($package) => $package->isTrial())->values())
<section class="section {{ $paid->count() <= 4 ? 'plans-fit' : '' }}" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <div class="head">
                <div>
                    <h2 class="display">{{ $text['title_1'] ?? '' }}@if (! empty($text['title_2'])) <em>{{ $text['title_2'] }}</em>@endif</h2>
                </div>
                @if (! empty($text['description']))<p>{{ $text['description'] }}</p>@endif
                @unless ($paid->isEmpty())
                    <div class="plans-controls">
                    <button type="button" class="plans-arrow" data-dir="-1" aria-label="{{ __('site.pricing.prev') }}"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3L5 8l5 5"/></svg></button>
                    <button type="button" class="plans-arrow" data-dir="1" aria-label="{{ __('site.pricing.next') }}"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6 3l5 5-5 5"/></svg></button>
                </div>
                @endunless
            </div>
        @if ($packages->isEmpty())
            <p class="empty">{{ $text['empty_text'] ?? '' }}</p>
        @else
            @php($showDevices = $packages->pluck('max_devices')->unique()->count() > 1)
            @foreach ($trials as $package)
                <div class="plan plan--trial plan--banner">
                    <h3>{{ $package->name }}</h3>
                    <span class="plan-badge">{{ __('site.pricing.badge_free') }}</span>
                    <div class="price price--free">{{ __('site.pricing.free') }}</div>
                    <ul class="facts">
                        <li><span>{{ __('site.pricing.accounts') }}</span><b>{{ $package->max_accounts }}</b></li>
                        @if ($showDevices)<li><span>{{ __('site.pricing.devices') }}</span><b>{{ $package->max_devices }}</b></li>@endif
                        <li><span>{{ __('site.pricing.term') }}</span><b>{{ $package->durationLabel() }}</b></li>
                    </ul>
                    <a class="btn outline" href="#contact" data-plan="{{ $package->name }}" data-prefill="{{ __('site.pricing.prefill') }}">{{ $text['trial_text'] ?? __('site.pricing.trial_button') }} <svg class="arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
                </div>
            @endforeach
            @php($isSlider = $paid->isNotEmpty())
            @if ($paid->isNotEmpty())
            <div class="plans-slider" data-plans-slider>
            <div class="plans plans--track">
                @foreach ($paid as $package)
                    <div class="plan">
                        <h3>{{ $package->name }}</h3>
                        <div class="price"><sup>$</sup>{{ $package->price }}</div>
                        <ul class="facts">
                            <li><span>{{ __('site.pricing.accounts') }}</span><b>{{ $package->max_accounts }}</b></li>
                            @if ($showDevices)<li><span>{{ __('site.pricing.devices') }}</span><b>{{ $package->max_devices }}</b></li>@endif
                            <li><span>{{ __('site.pricing.term') }}</span><b>{{ $package->durationLabel() }}</b></li>
                        </ul>
                        @php($buttonText = $package->isTrial() ? ($text['trial_text'] ?? __('site.pricing.trial_button')) : ($text['order_text'] ?? ''))
                        @if ($buttonText !== '')
                            @php($external = ! $package->isTrial() && $contactUrl)
                            <a class="btn outline" href="{{ $external ? $contactUrl : '#contact' }}" @if ($external) target="_blank" rel="noopener" @else data-plan="{{ $package->name }}" data-prefill="{{ __('site.pricing.prefill') }}" @endif @unless ($package->isTrial()) aria-label="{{ __('site.pricing.order_label', ['plan' => $package->name]).($external ? ' '.__('site.pricing.new_tab') : '') }}" @endunless>{{ $buttonText }} <svg class="arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
                        @endif
                    </div>
                @endforeach
            </div>
            </div>
            @endif
        @endif
        @if (! empty($text['note']))
            <p class="note">{{ $text['note'] }}</p>
        @endif
    </div>
</section>
