@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
@php($cardTag = ($settings['heading_tag'] ?? 'h2') === 'h1' ? 'h2' : 'h3')
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
                        <a href="{{ url()->current() }}" class="kb-all {{ $active === null ? 'is-active' : '' }}" @if ($active === null) aria-current="true" @endif>{{ ($text['all_label'] ?? '') ?: __('site.all') }}</a>
                        @foreach ($categories as $category)
                            <a href="{{ url()->current().'?category='.urlencode($category) }}" class="kb-c-{{ \App\Models\Page::categoryColor($category) }} {{ $active === $category ? 'is-active' : '' }}" @if ($active === $category) aria-current="true" @endif>{{ $category }}</a>
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
                        @if ($article->og_image)
                            @php($cover = $moduleFiles->url($article->og_image))
                            @php($coverBase = preg_replace('/\.(jpe?g|png)$/i', '', $cover))
                            @php($coverWebp = str_starts_with($cover, '/') && $coverBase !== $cover && is_file(public_path(ltrim($coverBase, '/').'-640.webp')))
                            <span class="kb-cover">
                                @if ($coverWebp)<picture><source type="image/webp" srcset="{{ $coverBase }}-640.webp 640w, {{ $coverBase }}-960.webp 960w" sizes="(min-width: 1280px) 371px, (min-width: 1024px) calc((100vw - 112px) / 3 - 18px), (min-width: 640px) calc((100vw - 80px) / 2 - 18px), calc(100vw - 50px)">@endif
                                <img src="{{ $cover }}" alt="{{ $article->title }}" width="1200" height="750" @if ($loop->first) fetchpriority="high" @elseif ($loop->index > 2) loading="lazy" @endif decoding="async">
                                @if ($coverWebp)</picture>@endif
                            </span>
                        @endif
                        <span class="kb-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ __('site.date_with_year', ['date' => $article->articleDate()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</span>
                        @if ($article->category)<span class="kb-tags"><span class="kb-tag kb-c-{{ \App\Models\Page::categoryColor($article->category) }}">{{ $article->category }}</span></span>@endif
                        <{{ $cardTag }}>{{ $article->title }}</{{ $cardTag }}>
                        @if ($article->excerpt)<p>{{ $article->excerpt }}</p>@endif
                    </a>
                @endforeach
            </div>
            {{ $articles->links('vendor.pagination.kb') }}
        @endif
    </div>
</section>
