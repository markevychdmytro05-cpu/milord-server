@php($locale = \App\Support\SiteLocale::current())
@inject('moduleFiles', 'App\Services\ModuleService')
<div class="main-banner__block">
    <div class="main-banner__video-wrap">
        <img class="main-banner__block__img" src="{{ $moduleFiles->url($settings['upload']) }}" alt="{{ $settings[$locale]['title'] ?? '' }}" loading="lazy">
    </div>
</div>
