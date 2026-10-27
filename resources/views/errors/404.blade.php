<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.not_found.title') }} | Numis</title>
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#f5f5f5">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        @font-face{font-family:'Inter';font-weight:400 600;font-display:swap;src:url('{{ Vite::asset('resources/fonts/inter-cyrillic.woff2') }}') format('woff2');unicode-range:U+0301,U+0400-045F,U+0490-0491,U+2116}
        @font-face{font-family:'Inter';font-weight:400 600;font-display:swap;src:url('{{ Vite::asset('resources/fonts/inter-latin.woff2') }}') format('woff2');unicode-range:U+0020-007E,U+00A0,U+00A9,U+00AB,U+00AE,U+00B0-00B3,U+00B5,U+00B7,U+00BB,U+00D7,U+00F7,U+02BB-02BC,U+2010-2027,U+2030-203A,U+20AC,U+2122,U+2190-2193,U+2212,U+2215}
        @font-face{font-family:'Geist';font-weight:300;font-display:swap;src:url('{{ Vite::asset('resources/fonts/geist-latin.woff2') }}') format('woff2');unicode-range:U+0020-007E,U+00A0,U+00A9,U+00AB,U+00AE,U+00B0-00B3,U+00B5,U+00B7,U+00BB,U+00D7,U+00F7,U+02BB-02BC,U+2010-2027,U+2030-203A,U+20AC,U+2122,U+2190-2193,U+2212,U+2215}
        :root{--canvas:#f5f5f5;--ink:#0c0a09;--body:#4e4e4e;--primary:#292524;--primary-active:#0c0a09;--on-primary:#fff;--orb-mint:#a7e5d3;--orb-lavender:#c8b8e0;--orb-peach:#f4c5a8}
        *{box-sizing:border-box}
        body{position:relative;margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;padding:24px 16px;overflow-x:hidden;background:var(--canvas);color:var(--body);font-family:Inter,system-ui,sans-serif;text-align:center}
        body::before{content:'';position:absolute;top:50%;left:50%;z-index:-1;width:min(760px,100vw);aspect-ratio:1;translate:-50% -60%;background:radial-gradient(closest-side,color-mix(in srgb,var(--orb-lavender) 70%,transparent),transparent),radial-gradient(closest-side at 70% 70%,color-mix(in srgb,var(--orb-mint) 60%,transparent),transparent)}
        .code{margin:0;color:var(--ink);font-family:Geist,system-ui,sans-serif;font-size:clamp(112px,24vw,240px);font-weight:300;line-height:1;letter-spacing:-.05em}
        .nf{display:contents}.text{margin:0;font-size:18px;font-weight:400;line-height:1.5;color:var(--body)}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:12px 24px;border-radius:9999px;background:var(--primary);color:var(--on-primary);font-size:15px;font-weight:500;text-decoration:none;transition:background-color .2s}
        .btn:hover{background:var(--primary-active)}
        .btn:focus-visible{outline:2px solid var(--ink);outline-offset:2px}
    </style>
</head>
<body>
    <main class="nf">
        <p class="code" aria-hidden="true">404</p>
        <h1 class="text">{!! __('site.not_found.text') !!}</h1>
        <a class="btn" href="{{ route('home') }}">{{ __('site.not_found.home') }}</a>
    </main>
</body>
</html>
