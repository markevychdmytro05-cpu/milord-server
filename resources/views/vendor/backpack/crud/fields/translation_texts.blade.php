{{-- Текст перекладу для кожної мови сайту: name="text[uk]". Значення – масив {мова: текст} моделі LanguageLine. --}}
@php
    $values = (array) (old($field['name']) ?? $field['value'] ?? []);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @foreach (\App\Support\SiteLocale::all() as $locale)
        <div class="mb-2">
            <div class="text-muted small mb-1">{{ strtoupper($locale) }}{{ $locale === \App\Support\SiteLocale::current() ? ' *' : '' }}</div>
            <textarea class="form-control" name="{{ $field['name'] }}[{{ $locale }}]" rows="2">{{ $values[$locale] ?? '' }}</textarea>
        </div>
    @endforeach
    @if (! empty($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')
