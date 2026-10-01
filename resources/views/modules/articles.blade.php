@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
@inject('moduleFiles', 'App\Services\ModuleService')
<section class="section kb" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        @if (! empty($text['title']) || ! empty($settings['show_filters']))
            <div class="kb-top">
                @if (! empty($text['title']))
                    <div>
                        @php($tag = ($settings['heading_tag'] ?? 'h2') === 'h1' ? 'h1' : 'h2')
                        <{{ $tag }} class="display">{{ $text['title'] }}</{{ $tag }}>
                        @if (! empty($text['description']))<p class="kb-lead">{{ $text['description'] }}</p>@endif
                    </div>
                @endif
                @if (! empty($settings['show_filters']) && $categories->count() > 1)
                    <nav class="kb-filters" aria-label="{{ __('site.categories') }}">
                        <a href="{{ url()->current() }}" class="kb-all {{ $active === null ? 'is-active' : '' }}">{{ ($text['all_label'] ?? '') ?: __('site.all') }}</a>
                        @foreach ($categories as $category)
                            <a href="{{ url()->current().'?category='.urlencode($category) }}" class="kb-c-{{ \App\Models\Page::categoryColor($category) }} {{ $active === $category ? 'is-active' : '' }}">{{ $category }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>
        @endif

        @if ($articles->isEmpty())
            <p class="empty">{{ $text['empty_text'] ?? '' }}</p>
        @else
            <div class="kb-grid">
                @foreach ($articles as $article)
                    <a class="kb-card" href="{{ $article->url() }}">
                        <span class="kb-cover">
                            @if ($article->og_image)
                                <img src="{{ $moduleFiles->url($article->og_image) }}" alt="{{ $article->title }}" loading="lazy">
                            @else
                                @include('partials.kb-cover-fallback')
                            @endif
                        </span>
                        <span class="kb-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ __('site.date_with_year', ['date' => $article->articleDate()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</span>
                        @if ($article->category)<span class="kb-tags"><span class="kb-tag kb-c-{{ \App\Models\Page::categoryColor($article->category) }}">{{ $article->category }}</span></span>@endif
                        <h3>{{ $article->title }}</h3>
                        @if ($article->excerpt)<p>{{ $article->excerpt }}</p>@endif
                    </a>
                @endforeach
            </div>
            {{ $articles->links('vendor.pagination.kb') }}
        @endif
    </div>
</section>
