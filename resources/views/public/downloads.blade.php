@extends('layouts.public')

@section('title', __('downloads.title', ['brand' => \App\Models\SiteSetting::current()->header_logo]))
@section('description', __('downloads.description'))
@section('canonical', route('downloads.index'))
@section('robots', 'index, follow')

@section('content')
    <section class="section kb kb--page dl-page">
        <div class="wrap">
            <nav class="crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a>
                <span>{{ __('downloads.breadcrumb') }}</span>
            </nav>
            <header class="dl-intro">
                <h1 class="display">{{ __('downloads.heading') }}</h1>
                <p>{{ __('downloads.lead') }}</p>
            </header>

            @if ($releases->isEmpty())
                <p class="empty">{{ __('downloads.empty') }}</p>
            @else
                @php($platformReleases = $releases->groupBy('platform'))
                <div class="dl-platforms">
                    @foreach (\App\Models\Release::PLATFORMS as $platform => $label)
                        @continue(! $platformReleases->has($platform))
                        <section class="dl-platform" aria-labelledby="dl-platform-{{ $platform }}">
                            <div class="dl-platform-head">
                                <span class="dl-platform-icon" aria-hidden="true">
                                    @if ($platform === 'win')
                                        <svg viewBox="0 0 24 24"><path d="M2 4.5 10.5 3v8.5H2zm10.5-1.8L22 1v10.5h-9.5zM2 13h8.5v8.5L2 20zm10.5 0H22v10l-9.5-1.7z" fill="currentColor"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24"><rect x="4" y="3.5" width="16" height="12" rx="1.5"/><path d="M2.5 19h19M9 19l.6-3.5m4.8 0L15 19"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <h2 id="dl-platform-{{ $platform }}">{{ $label }}</h2>
                                    <p>{{ __('downloads.platform_hint') }}</p>
                                </div>
                            </div>
                            <div class="dl-builds">
                                @foreach ($platformReleases[$platform] as $release)
                                    <div class="dl-build">
                                        <div class="dl-build-info">
                                            <strong>{{ \App\Models\Release::ARCHES[$release->arch] }}</strong>
                                            <span>{{ __('downloads.arch_hint.'.$release->platform.'.'.$release->arch) }}</span>
                                            <small>{{ __('downloads.version', ['version' => $release->version, 'date' => $release->published_at?->format('d.m.Y')]) }} · {{ $release->sizeLabel() }}</small>
                                        </div>
                                        <a class="dl-download" href="{{ route('downloads.start', $release->target()) }}" rel="nofollow" aria-label="{{ __('downloads.button', ['target' => $release->label()]) }}">
                                            {{ __('downloads.download') }}
                                            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2.5v10m0 0 3.5-3.5M10 12.5 6.5 9M3.5 15v2h13v-2"/></svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                <div class="dl-next">
                    <div>
                        <strong>{{ __('downloads.license_title') }}</strong>
                        <p>{{ __('downloads.license_hint') }}</p>
                    </div>
                    <a href="{{ route('home').'#contact' }}">{{ __('downloads.license_button') }} <span aria-hidden="true">→</span></a>
                </div>
                <p class="dl-footnote">{{ __('downloads.unsigned_hint') }}</p>
            @endif
        </div>
    </section>
@endsection
