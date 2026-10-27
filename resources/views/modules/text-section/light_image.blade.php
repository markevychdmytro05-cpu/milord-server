@php($locale = \App\Support\SiteLocale::current())
@inject('moduleFiles', 'App\Services\ModuleService')
<div class="answers__block">
    <img class="answers__block__img" src="{{ $moduleFiles->url($settings['upload']) }}" alt="{{ $settings[$locale]['title'] ?? '' }}" width="400" height="400" loading="lazy">
</div>
