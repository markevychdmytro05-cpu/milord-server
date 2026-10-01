<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', __('site.seo.default_description'))">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="alternate" type="text/plain" href="{{ route('llms') }}" title="llms.txt">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#0a0a0a">
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
    @endif
    @stack('head')
    @vite('resources/css/public.css')
</head>
<body class="{{ $site->hasTopbar() ? 'has-topbar' : '' }}">
    <div class="site-head">
        @if ($site->hasTopbar())
            <div class="topbar">
                <div class="topbar-in">
                    @if ($site->contact_email)
                        <a href="mailto:{{ $site->contact_email }}"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>{{ $site->contact_email }}</a>
                    @endif
                    @if ($site->contact_phone)
                        <a href="{{ $site->phoneUrl() }}"><svg viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.500 1.500a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg>{{ $site->contact_phone }}</a>
                    @endif
                    @if ($site->contact_address)
                        <span class="topbar-address"><svg viewBox="0 0 24 24"><path d="M12 21s7-6.100 7-11a7 7 0 10-14 0c0 4.900 7 11 7 11z"/><circle cx="12" cy="10" r="2.500"/></svg>{{ $site->contact_address }}</span>
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
                            <div class="dropdown">
                                <a href="{{ $item->href() ?? '#' }}" class="has-children {{ $item->isCurrent($currentPath) || $item->hasCurrentChild($currentPath) ? 'is-active' : '' }}" @if ($item->isCurrent($currentPath) || $item->hasCurrentChild($currentPath)) aria-current="page" @endif @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                                <button class="dropdown-toggle" type="button" aria-expanded="false" aria-label="{{ __('site.nav.expand_section', ['name' => $item->label]) }}"><svg aria-hidden="true" viewBox="0 0 7 5" width="12" height="9"><path d="M.5.5l3 3.5 3-3.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                <div class="dropdown-list">
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
                        <a class="nav-cta" href="{{ $site->header_button_url }}">{{ $site->header_button_text }}</a>
                    @endif
                    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links" aria-label="{{ __('site.nav.open') }}">
                        <svg class="nav-toggle__open" aria-hidden="true" width="22" height="14" viewBox="0 0 22 14" fill="none"><path d="M1 14h20a1 1 0 100-2H1a1 1 0 100 2zm0-6h20a1 1 0 100-2H1a1 1 0 000 2zM0 1a1 1 0 001 1h20a1 1 0 100-2H1a1 1 0 00-1 1z" fill="currentColor"/></svg>
                        <svg class="nav-toggle__close" aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M1.05 0L8 6.95 14.95 0 16 1.05 9.05 8 16 14.95 14.95 16 8 9.05 1.05 16 0 14.95 6.95 8 0 1.05z" fill="currentColor"/></svg>
                    </button>
                </div>
            </div>
        </nav>
    </div>

    @if ($messengers)
        <aside class="float-messengers" aria-label="{{ __('site.messengers') }}">
            @foreach ($messengers as $name => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $name }}" title="{{ $name }}">@include('partials.messenger-icon', ['name' => $name])</a>
            @endforeach
        </aside>
    @endif

    @yield('content')

    <footer class="site-foot">
        @if ($footerModules !== '')
            <div class="foot-form foot-modules">{!! $footerModules !!}</div>
        @endif

        <div class="foot-info wrap">
            <div class="foot-brand-col">
                <a class="logo foot-logo" href="{{ route('home') }}" aria-label="{{ $site->header_logo }}">@include('partials.logo-mark')<span>{{ $site->header_logo }}</span></a>
                <p class="foot-copy">{{ $site->footer_text }}</p>
            </div>

            <div class="foot-content">
                @if ($footerNav->isNotEmpty())
                    <nav class="foot-nav" aria-label="{{ __('site.footer.nav') }}">
                        @foreach ($footerNav as $link)
                            <a href="{{ $link->href() }}" @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
                        @endforeach
                    </nav>
                @endif

                @if ($site->hasFooterContacts())
                    <div class="foot-contacts">
                        @if ($site->footer_heading)<h2 class="foot-heading">{{ $site->footer_heading }}</h2>@endif
                        <div class="foot-contacts-row">
                            @if ($site->contact_email)
                                <div><span>{{ ($site->contact_email_label ?: 'Email').':' }}</span><a href="mailto:{{ $site->contact_email }}">{{ $site->contact_email }}</a></div>
                            @endif
                            @if ($site->contact_phone)
                                <div><span>{{ __('site.footer.phone') }}</span><a href="{{ $site->phoneUrl() }}">{{ $site->contact_phone }}</a></div>
                            @endif
                            @if ($site->contact_email2)
                                <div><span>{{ ($site->contact_email2_label ?: 'Email').':' }}</span><a href="mailto:{{ $site->contact_email2 }}">{{ $site->contact_email2 }}</a></div>
                            @endif
                            @if ($site->contact_address)
                                <div><span>{{ __('site.footer.address') }}</span><b>{{ $site->contact_address }}</b></div>
                            @endif
                            @if ($messengers)
                                <div>
                                    <span>{{ __('site.footer.social') }}</span>
                                    <div class="foot-social">
                                        @foreach ($messengers as $name => $url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $name }}" title="{{ $name }}">@include('partials.messenger-icon', ['name' => $name])</a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($footerColumns->isNotEmpty())
                    <nav class="foot-legal" aria-label="{{ __('site.footer.legal') }}">
                        @foreach ($footerColumns as $column)
                            @foreach ($column['items'] as $link)
                                <a href="{{ $link->href() }}" @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
                            @endforeach
                        @endforeach
                    </nav>
                @endif
            </div>
        </div>
    </footer>

    <div class="toast" id="toast" role="status" aria-live="polite" data-flash="{{ session('contact_sent') }}" hidden>
        <span class="toast-text"></span>
        <button class="toast-close" type="button" aria-label="{{ __('site.toast.close') }}">&times;</button>
    </div>

    <script>
        (function () {
            var toast = document.getElementById('toast');
            var timer;

            function showToast(message, type) {
                if (!toast || !message) { return; }
                toast.querySelector('.toast-text').textContent = message;
                toast.classList.toggle('is-error', type === 'error');
                toast.hidden = false;
                requestAnimationFrame(function () { toast.classList.add('is-visible'); });
                clearTimeout(timer);
                timer = setTimeout(hideToast, 6000);
            }

            function hideToast() {
                if (!toast) { return; }
                toast.classList.remove('is-visible');
                setTimeout(function () { toast.hidden = true; }, 350);
            }

            if (toast) {
                toast.querySelector('.toast-close').addEventListener('click', hideToast);
                if (toast.dataset.flash) { showToast(toast.dataset.flash); }
            }

            function clearErrors(form) {
                form.querySelectorAll('.contact-error').forEach(function (el) { el.remove(); });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest ? event.target.closest('.contact-form') : null;
                if (!form || !window.fetch) { return; }
                event.preventDefault();

                var button = form.querySelector('button[type=submit]');
                button.disabled = true;
                form.classList.add('is-loading');
                clearErrors(form);

                fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (response) { return response.json().catch(function () { return {}; }).then(function (data) { return { response: response, data: data }; }); })
                    .then(function (result) {
                        if (result.response.ok) {
                            form.reset();
                            showToast(form.dataset.success || result.data.message);
                        } else if (result.response.status === 422 && result.data.errors) {
                            Object.keys(result.data.errors).forEach(function (name) {
                                var field = form.querySelector('[name="' + name + '"]');
                                var label = field && field.closest('.contact-field');
                                if (label) {
                                    var error = document.createElement('em');
                                    error.className = 'contact-error';
                                    error.textContent = result.data.errors[name][0];
                                    label.appendChild(error);
                                }
                            });
                            showToast(Object.values(result.data.errors)[0][0], 'error');
                        } else if (result.response.status === 429) {
                            showToast(@json(__('site.toast.too_many')), 'error');
                        } else {
                            showToast(@json(__('site.toast.failed')), 'error');
                        }
                    })
                    .catch(function () { showToast(@json(__('site.toast.network')), 'error'); })
                    .finally(function () { button.disabled = false; form.classList.remove('is-loading'); });
            });
        })();
    </script>

    <script>
        (function () {
            var root = document.documentElement;
            var nav = document.querySelector('.nav');
            var toggle = document.querySelector('.nav-toggle');
            var links = document.querySelector('.links');
            function closeSections(except) {
                document.querySelectorAll('.dropdown.is-open').forEach(function (item) {
                    if (item !== except) { item.classList.remove('is-open'); item.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false'); }
                });
            }
            function setMenu(open) {
                if (!nav || !toggle) { return; }
                nav.classList.toggle('is-open', open);
                root.classList.toggle('menu-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? @json(__('site.nav.close')) : @json(__('site.nav.open')));
                if (!open) { closeSections(null); }
            }
            if (toggle && links) {
                toggle.addEventListener('click', function () { setMenu(!nav.classList.contains('is-open')); });
                document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { setMenu(false); } });
                document.addEventListener('click', function (event) {
                    if (nav.classList.contains('is-open') && !event.target.closest('.nav')) { setMenu(false); }
                });
                links.addEventListener('click', function (event) {
                    var button = event.target.closest('.dropdown-toggle');
                    if (button) {
                        var item = button.parentElement;
                        var open = !item.classList.contains('is-open');
                        closeSections(item);
                        item.classList.toggle('is-open', open);
                        button.setAttribute('aria-expanded', open ? 'true' : 'false');
                        return;
                    }
                    var link = event.target.closest('a[href]');
                    if (link && (!link.classList.contains('has-children') || link.getAttribute('href') !== '#')) { setMenu(false); }
                });
                window.addEventListener('resize', function () { if (window.innerWidth > 1000) { setMenu(false); } });
            }
            function onScroll() { if (nav) { nav.classList.toggle('is-scrolled', window.scrollY > 8); } }

            // Підсвічування розділу в меню під час прокрутки: лише для якорів на поточну сторінку (/#pricing на головній).
            var spy = [].slice.call(document.querySelectorAll('.links a[href*="#"]')).map(function (link) {
                var url = new URL(link.href, location.href);
                var target = url.pathname === location.pathname && url.hash.length > 1 ? document.querySelector(url.hash) : null;
                return target ? { link: link, target: target } : null;
            }).filter(Boolean);
            var spyFrame = 0;
            function updateSpy() {
                spyFrame = 0;
                var line = window.innerHeight * 0.35, current = null;
                spy.forEach(function (item) {
                    var rect = item.target.getBoundingClientRect();
                    if (rect.top <= line && rect.bottom > line) { current = item; }
                });
                spy.forEach(function (item) {
                    var on = item === current;
                    item.link.classList.toggle('is-active', on);
                    if (on) { item.link.setAttribute('aria-current', 'location'); } else { item.link.removeAttribute('aria-current'); }
                });
            }
            if (spy.length) {
                window.addEventListener('scroll', function () { if (!spyFrame) { spyFrame = requestAnimationFrame(updateSpy); } }, { passive: true });
                window.addEventListener('resize', updateSpy);
                updateSpy();
            }
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });

            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

            var selector = '.head, .card, .step, .plan, .accordion__item, .hero-visual, .contact-form-wrap, .foot-col, .page-article .prose, .benefit-section__block-col, .answers__block, .main-banner__block, .cta-row__wrap, .our-clients__col, .notice, .note, .empty';
            var items = [].slice.call(document.querySelectorAll(selector));
            if (!items.length) { return; }

            root.classList.add('js');
            items.forEach(function (el) {
                var siblings = [].slice.call(el.parentNode.children).filter(function (s) { return s.matches(selector); });
                el.style.setProperty('--reveal-delay', Math.min(Math.max(0, siblings.indexOf(el)), 6) * 70 + 'ms');
                el.classList.add('reveal');
            });

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var target = entry.target;
                        target.classList.add('is-visible');
                        observer.unobserve(target);
                        // Після появи повертаємо власні (повільніші) переходи наведення: reveal-перехід їх перекриває.
                        setTimeout(function () { target.classList.add('reveal-done'); }, 700 + parseInt(target.style.getPropertyValue('--reveal-delay') || '0', 10) + 50);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
            items.forEach(function (el) { observer.observe(el); });
        })();
    </script>

    <script>
        document.addEventListener('click', function (event) {
            var toggler = event.target.closest('.accordion__toggler');
            if (!toggler) { return; }
            toggler.setAttribute('aria-expanded', toggler.closest('.accordion__item').classList.toggle('is-open'));
        });
    </script>

</body>
</html>
