@php($locale = \App\Support\SiteLocale::current())
@php($text = $settings[$locale] ?? [])
<section class="section changelog-section" @if (! empty($settings['anchor'])) id="{{ $settings['anchor'] }}" @endif>
    <div class="wrap">
        <div class="head">
            <div>
                @if (! empty($text['title']))<h2 class="display">{{ $text['title'] }}</h2>@endif
            </div>
            <a class="changelog-all" href="{{ $url }}">{{ __('site.changelog.all') }} &rarr;</a>
        </div>
        <ol class="changelog">
            @foreach ($entries as $entry)
                <li class="changelog-entry">
                    <time class="changelog-date">{{ $entry['date'] }}</time>
                    <ul>
                        @foreach ($entry['items'] as $item)
                            <li>{!! strip_tags($item, '<strong><a>') !!}</li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ol>
    </div>
</section>
