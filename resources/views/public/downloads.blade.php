@extends('layouts.public')

@section('title', __('downloads.title', ['brand' => \App\Models\SiteSetting::current()->header_logo]))
@section('description', __('downloads.description'))
@section('canonical', route('downloads.index'))
@section('robots', $releases->isEmpty() ? 'noindex, follow' : 'index, follow')

@push('head')
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect(__('downloads.faq'))->map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => __('site.breadcrumbs.home'), 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => __('downloads.breadcrumb'), 'item' => route('downloads.index')],
    ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <section class="section kb kb--page dl-page">
        <div class="wrap">
            <nav class="crumbs" aria-label="{{ __('site.breadcrumbs.label') }}">
                <ol>
                    <li><a href="{{ route('home') }}">{{ __('site.breadcrumbs.home') }}</a></li>
                    <li aria-current="page">{{ __('downloads.breadcrumb') }}</li>
                </ol>
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
                    <a class="btn ghost" href="{{ route('home').'#contact' }}">{{ __('downloads.license_button') }} <svg class="arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg></a>
                </div>
                <p class="dl-footnote">{{ __('downloads.unsigned_hint') }}</p>
            @endif

            <section class="dl-guide prose" aria-labelledby="dl-install">
                <h2 id="dl-install">{{ __('downloads.install_heading') }}</h2>
                <ol>
                    @foreach (__('downloads.install_steps') as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
                <p>{{ __('downloads.install_more') }}
                    <a href="{{ route('articles.show', 'iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia') }}">{{ __('downloads.install_link_connect') }}</a>,
                    <a href="{{ route('articles.show', 'chek-lyst-pered-startom-prodazhu-monety-nbu') }}">{{ __('downloads.install_link_checklist') }}</a>.
                </p>
            </section>
        </div>
    </section>
    @include('partials.faq-accordion', ['title' => __('downloads.faq_heading'), 'items' => collect(__('downloads.faq'))->map(fn ($item) => ['question' => $item['q'], 'answer' => '<p>'.e($item['a']).'</p>'])->all()])
@endsection
