<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', __('site.seo.default_description'))">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="alternate" hreflang="uk" href="@yield('canonical', url()->current())">
    <link rel="alternate" hreflang="x-default" href="@yield('canonical', url()->current())">
    <link rel="alternate" type="application/rss+xml" href="{{ route('feed') }}" title="{{ $site->header_logo }}: блог">
    <link rel="alternate" type="text/plain" href="{{ route('llms') }}" title="llms.txt">
    {{-- Google показує у видачі лише растрові іконки (ICO/PNG), тож вони йдуть першими. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="64x64">
    <link rel="icon" type="image/png" href="{{ asset('favicon-192.png') }}" sizes="192x192">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#f5f5f5">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="Numis">
    <meta property="og:locale" content="uk_UA">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', __('site.seo.default_description'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('description', __('site.seo.default_description'))">
    @php($ogImage = trim($__env->yieldContent('og_image')) ?: asset('images/og-default.jpg'))
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    @if ($ogImage === asset('images/og-default.jpg'))
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ __('site.seo.og_image_alt') }}">
    @else
        {{-- Розміри локальної картинки: соцмережі й месенджери показують прев’ю без очікування завантаження. --}}
        @php($ogFile = public_path(ltrim(parse_url($ogImage, PHP_URL_PATH) ?: '', '/')))
        @php($ogSize = is_file($ogFile) ? @getimagesize($ogFile) : false)
        @if ($ogSize)
            <meta property="og:image:width" content="{{ $ogSize[0] }}">
            <meta property="og:image:height" content="{{ $ogSize[1] }}">
        @endif
        <meta property="og:image:alt" content="@yield('og_image_alt', trim($__env->yieldContent('title')))">
    @endif
    @stack('head')
    @foreach (['inter-cyrillic', 'inter-latin', 'geist-cyrillic', 'geist-latin'] as $font)
        <link rel="preload" href="{{ Vite::asset('resources/fonts/'.$font.'.woff2') }}" as="font" type="font/woff2" crossorigin>
    @endforeach
    @vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body class="{{ $site->hasTopbar() ? 'has-topbar' : '' }}">
    <a class="skip-link" href="#main">{{ __('site.skip_to_content') }}</a>
    <header class="site-head">
        @if ($site->hasTopbar())
            <div class="topbar">
                <div class="topbar-in">
                    @if ($site->contact_email)
                        <a href="mailto:{{ $site->contact_email }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>{{ $site->contact_email }}</a>
                    @endif
                    @if ($site->contact_phone)
                        <a href="{{ $site->phoneUrl() }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.500 1.500a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg>{{ $site->contact_phone }}</a>
                    @endif
                    @if ($site->contact_address)
                        <span class="topbar-address"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.100 7-11a7 7 0 10-14 0c0 4.900 7 11 7 11z"/><circle cx="12" cy="10" r="2.500"/></svg>{{ $site->contact_address }}</span>
                    @endif
                </div>
            </div>
        @endif

        <nav class="nav" aria-label="{{ __('site.nav.label') }}">
            <div class="nav-in">
                <a class="logo" href="{{ route('home') }}" aria-label="{{ $site->header_logo }}">@include('partials.logo-mark')<span>{{ $site->header_logo }}</span></a>
                <div class="links" id="nav-links">
                    @php($currentPath = request()->path())
                    @foreach ($headerMenu as $item)
                        @if ($item->tree_children->isNotEmpty())
                            @php($sectionActive = $item->isCurrent($currentPath) || $item->hasCurrentChild($currentPath))
                            @php($submenuId = 'submenu-'.$loop->index)
                            {{-- Розділ без власної адреси – кнопка-розкривачка (APG Disclosure), а не посилання на «#». --}}
                            <div @class(['dropdown', 'is-active' => $sectionActive])>
                                @if ($item->href())
                                    <a href="{{ $item->href() }}" class="has-children {{ $sectionActive ? 'is-active' : '' }}" @if ($item->isCurrent($currentPath)) aria-current="page" @endif @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                                    <button class="dropdown-toggle" type="button" data-submenu-toggle aria-expanded="false" aria-controls="{{ $submenuId }}" aria-label="{{ __('site.nav.expand_section', ['name' => $item->label]) }}"><svg aria-hidden="true" viewBox="0 0 7 5" width="12" height="9"><path d="M.5.5l3 3.5 3-3.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                @else
                                    <button class="has-children dropdown-parent {{ $sectionActive ? 'is-active' : '' }}" type="button" data-submenu-toggle aria-expanded="false" aria-controls="{{ $submenuId }}">{{ $item->label }} <svg aria-hidden="true" viewBox="0 0 7 5" width="12" height="9"><path d="M.5.5l3 3.5 3-3.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                @endif
                                <div class="dropdown-list" id="{{ $submenuId }}">
                                    @foreach ($item->tree_children as $child)
                                        <a href="{{ $child->href() }}" @if ($child->isCurrent($currentPath)) class="is-active" aria-current="page" @endif @if ($child->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $child->label }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($item->href())
                            <a href="{{ $item->href() }}" @if ($item->isCurrent($currentPath)) class="is-active" aria-current="page" @endif @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                        @endif
                    @endforeach
                </div>
                <div class="nav-right">
                    @if ($site->header_button_text && $site->header_button_url)
                        {{-- На вузьких екранах лишаються перші два слова («Пробний ключ»), хвіст ховається. --}}
                        @php($ctaWords = preg_split('/\s+/u', trim($site->header_button_text)))
                        @php([$ctaFirst, $ctaRest] = [implode(' ', array_slice($ctaWords, 0, 2)), implode(' ', array_slice($ctaWords, 2))])
                        <a class="nav-cta" href="{{ $site->header_button_url }}">{{ $ctaFirst }}@if ($ctaRest !== '') <span class="nav-cta__rest">{{ $ctaRest }}</span>@endif</a>
                    @endif
                    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links" aria-label="{{ __('site.nav.open') }}" data-label-open="{{ __('site.nav.open') }}" data-label-close="{{ __('site.nav.close') }}">
                        <svg class="nav-toggle__open" aria-hidden="true" viewBox="0 0 18 12" fill="none"><path d="M1 1h16M1 6h16M1 11h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <svg class="nav-toggle__close" aria-hidden="true" viewBox="0 0 13 13" fill="none"><path d="M1 1l11 11M12 1L1 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <main id="main" tabindex="-1">
        @yield('content')
    </main>

    <footer class="site-foot">
        @if ($footerModules !== '')
            <div class="foot-form foot-modules">{!! $footerModules !!}</div>
        @endif

        <div class="foot-info wrap">
            <div class="foot-brand-col">
                <a class="logo foot-logo" href="{{ route('home') }}" aria-label="{{ $site->header_logo }}">@include('partials.logo-mark')<span>{{ $site->header_logo }}</span></a>
                @if ($site->footer_text)<p class="foot-copy">{{ $site->footer_text }}</p>@endif
                @if ($messengers)
                    <div class="foot-social">
                        @foreach ($messengers as $name => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $name }}" title="{{ $name }}">@include('partials.messenger-icon', ['name' => $name])</a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($footerNav->isNotEmpty())
                <nav class="foot-nav" aria-labelledby="foot-nav-title">
                    <h2 class="foot-heading" id="foot-nav-title">{{ __('site.footer.site') }}</h2>
                    @foreach ($footerNav as $link)
                        <a href="{{ $link->href() }}" @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($site->hasFooterContacts())
                <div class="foot-contacts">
                    <h2 class="foot-heading">{{ $site->footer_heading ?: __('site.footer.contacts') }}</h2>
                    <div class="foot-contacts-row">
                        @if ($site->contact_email)
                            <div><span>{{ $site->contact_email_label ?: 'Email' }}</span><a href="mailto:{{ $site->contact_email }}">{{ $site->contact_email }}</a></div>
                        @endif
                        @if ($site->contact_phone)
                            <div><span>{{ __('site.footer.phone') }}</span><a href="{{ $site->phoneUrl() }}">{{ $site->contact_phone }}</a></div>
                        @endif
                        @if ($site->contact_email2)
                            <div><span>{{ $site->contact_email2_label ?: 'Email' }}</span><a href="mailto:{{ $site->contact_email2 }}">{{ $site->contact_email2 }}</a></div>
                        @endif
                        @if ($site->contact_address)
                            <div><span>{{ __('site.footer.address') }}</span><b>{{ $site->contact_address }}</b></div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($footerColumns->isNotEmpty())
                <nav class="foot-legal" aria-labelledby="foot-legal-title">
                    <h2 class="foot-heading" id="foot-legal-title">{{ __('site.footer.legal') }}</h2>
                    @foreach ($footerColumns as $column)
                        @foreach ($column['items'] as $link)
                            <a href="{{ $link->href() }}" @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
                        @endforeach
                    @endforeach
                </nav>
            @endif
        </div>
    </footer>

    {{-- Живий регіон існує від початку, тож зчитувачі екрана озвучують результат відправки форми. --}}
    <div class="visually-hidden" id="live-status" role="status" aria-live="polite"></div>
    <div class="toast" id="toast" role="status" aria-live="polite" data-flash="{{ session('contact_sent') ?? session('community_status') }}" data-flash-error="{{ session('community_error') }}" hidden>
        <span class="toast-text"></span>
        <button class="toast-close" type="button" aria-label="{{ __('site.toast.close') }}">&times;</button>
    </div>




</body>
</html>
