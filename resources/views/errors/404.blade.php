<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.not_found.title') }}</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:28px;padding:24px;background:#fff;color:#111;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;text-align:center}
        .code{margin:0;color:#1f2050;font-size:clamp(120px,26vw,260px);font-weight:400;line-height:1;letter-spacing:-.02em}
        .text{margin:0;font-size:18px;line-height:1.5;text-transform:uppercase}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-width:240px;height:56px;padding:0 32px;border-radius:10px;background:#5b5fd1;color:#fff;font-size:18px;font-weight:700;text-decoration:none;transition:background-color .2s}
        .btn:hover{background:#4a4ec0}
        .btn:focus-visible{outline:3px solid #1f2050;outline-offset:3px}
    </style>
</head>
<body>
    <h1 class="code">404</h1>
    <p class="text">{!! __('site.not_found.text') !!}</p>
    <a class="btn" href="{{ route('home') }}">{{ __('site.not_found.home') }}</a>
</body>
</html>
