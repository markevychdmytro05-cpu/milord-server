@php($paths = [
    'news' => '<path d="M4 5h12v14H6a2 2 0 01-2-2V5z"/><path d="M16 9h4v8a2 2 0 01-2 2h-2"/><path d="M7 9h6M7 13h6"/>',
    'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 114 2c-.9.6-1.5 1.1-1.5 2.2"/><path d="M12 17h.01"/>',
    'review' => '<path d="M12 3.5l2.6 5.3 5.9.9-4.250 4.100 1 5.800L12 16.800 6.750 19.600l1-5.800L3.500 9.700l5.900-.9L12 3.500z"/>',
    'idea' => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 00-3.500 10.900c.6.5 1 1.200 1 2.100h5c0-.9.4-1.600 1-2.100A6 6 0 0012 3z"/>',
    'bug' => '<path d="M9 8a3 3 0 016 0"/><rect x="8" y="8" width="8" height="11" rx="4"/><path d="M12 12v7M4 12h4M16 12h4M5 7l3 2M19 7l-3 2M5 19l3-2.500M19 19l-3-2.500"/>',
    'discussion' => '<path d="M4 5h11v8H9l-3 3v-3H4V5z"/><path d="M18 9h2v8h-2v3l-3-3H9v-2"/>',
    'faq' => '<path d="M3 5.500C5 4.500 8 4.500 12 6.500v13c-4-2-7-2-9-1v-13z"/><path d="M21 5.500c-2-1-5-1-9 1v13c4-2 7-2 9-1v-13z"/><path d="M14.500 10l1.500 1.500 2.500-3"/>',
    'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
    'eye' => '<path d="M2.500 12S6 5.500 12 5.500 21.500 12 21.500 12 18 18.500 12 18.500 2.500 12 2.500 12z"/><circle cx="12" cy="12" r="2.800"/>',
    'comment' => '<path d="M4 5h16v11H9l-5 4V5z"/>',
    'heart' => '<path d="M12 20s-7.500-4.600-7.500-10A4.300 4.300 0 0112 7.500 4.300 4.300 0 0119.500 10c0 5.400-7.500 10-7.500 10z"/>',
    'share' => '<circle cx="6" cy="12" r="2.500"/><circle cx="17" cy="6" r="2.500"/><circle cx="17" cy="18" r="2.500"/><path d="M8.300 10.800l6.400-3.600M8.300 13.200l6.400 3.600"/>',
    'flag' => '<path d="M5 21V4M5 5h12l-2 4 2 4H5"/>',
])
<svg class="cm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
