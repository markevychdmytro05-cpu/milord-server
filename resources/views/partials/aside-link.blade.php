@php($asideCover = $item->og_image ? parse_url($item->ogImageUrl(), PHP_URL_PATH) : null)
@php($asideBase = $asideCover ? preg_replace('/\.(jpe?g|png)$/i', '', $asideCover) : null)
@php($asideWebp = $asideBase && $asideBase !== $asideCover && is_file(public_path(ltrim($asideBase, '/').'-640.webp')))
<a class="ra-card ra-card--photo" href="{{ $item->url() }}">
    @if ($asideCover)
        <span class="ra-cover">
            @if ($asideWebp)<picture><source type="image/webp" srcset="{{ $asideBase }}-640.webp" sizes="88px">@endif
            <img src="{{ $asideCover }}" alt="{{ $item->title }}" width="1200" height="750" loading="lazy" decoding="async">
            @if ($asideWebp)</picture>@endif
        </span>
    @endif
    <strong>{{ $item->title }}</strong>
</a>
