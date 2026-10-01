@if ($paginator->hasPages())
    {{-- Перша сторінка без параметра page: /monety-nbu, а не /monety-nbu?page=1. --}}
    @php($clean = fn (?string $url): ?string => $url === null ? null : rtrim(preg_replace('/([?&])page=1$/', '', $url), '?&'))
    <nav class="kb-pagination" aria-label="{{ __('site.pagination.label') }}">
        @if ($paginator->onFirstPage())
            <span class="kb-page kb-page--nav is-disabled" aria-disabled="true" aria-label="{{ __('site.pagination.previous') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></span>
        @else
            <a class="kb-page kb-page--nav" href="{{ $clean($paginator->previousPageUrl()) }}" rel="prev" aria-label="{{ __('site.pagination.previous') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="kb-page kb-page--gap" aria-hidden="true">{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="kb-page is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="kb-page" href="{{ $clean($url) }}" aria-label="{{ __('site.pagination.page', ['number' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="kb-page kb-page--nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('site.pagination.next') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></a>
        @else
            <span class="kb-page kb-page--nav is-disabled" aria-disabled="true" aria-label="{{ __('site.pagination.next') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></span>
        @endif
    </nav>
@endif
