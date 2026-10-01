@switch($name)
    @case('Telegram')
        <svg viewBox="0 0 24 24"><path d="M21 3L2.500 10.500l6 2 2 6.500 3-4 5 3.500z"/><path d="M8.500 12.500L21 3"/></svg>
        @break
    @case('WhatsApp')
        <svg viewBox="0 0 24 24"><path d="M3 21l1.700-4.900A8.500 8.500 0 118.100 19.700z"/><path d="M9 8.500c0 3.500 3 6.500 6.500 6.500l1-1.500-2-1-1 .8a4 4 0 01-2-2l.8-1-1-2z"/></svg>
        @break
    @case('Instagram')
        <svg viewBox="0 0 24 24"><rect x="3.500" y="3.500" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".6"/></svg>
        @break
    @case('Facebook')
        <svg viewBox="0 0 24 24"><path d="M14 8h3V4h-3a4 4 0 00-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/></svg>
        @break
    @default
        <svg viewBox="0 0 24 24"><path d="M5 4h14a1 1 0 011 1v10a1 1 0 01-1 1h-8l-4 4v-4H5a1 1 0 01-1-1V5a1 1 0 011-1z"/><path d="M9 9c0 2 1.500 3.500 3.500 3.500l.8-1-1.300-1-.7.5a2 2 0 01-1-1l.5-.7-1-1.300z"/></svg>
@endswitch
