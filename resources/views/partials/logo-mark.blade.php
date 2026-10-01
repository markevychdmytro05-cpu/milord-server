@if ($site->logo_image)
    <img class="logo-mark" src="{{ $site->logo_image }}" alt="" width="32" height="32">
@else
    <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="13.500" fill="none" stroke="currentColor" stroke-width="2.500"/><path d="M10.500 22V10l11 12V10" fill="none" stroke="currentColor" stroke-width="2.500" stroke-linejoin="miter"/></svg>
@endif
