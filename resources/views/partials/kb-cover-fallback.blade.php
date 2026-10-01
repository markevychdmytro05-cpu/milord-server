{{-- Єдина заглушка обкладинки картки: за замовчуванням іконка статті, для монет – іконка монети ($icon = 'coin'). --}}
<span class="kb-cover-fallback" aria-hidden="true">
    @if (($icon ?? 'article') === 'coin')
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5.5"/><path d="M12 9v6M9.5 12h5"/></svg>
    @else
        <svg viewBox="0 0 24 24"><path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6M9 17h3"/></svg>
    @endif
</span>
