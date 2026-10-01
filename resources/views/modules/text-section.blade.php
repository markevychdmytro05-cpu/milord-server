@php
    $hasImage = ! empty($settings['upload']);
    $prefix = ($settings['type'] ?? 'light') === 'gray' ? 'grey' : 'light';
    $imageFade = ($settings['position'] ?? 'left') === 'right' ? 'left' : 'right';
    $textFade = ($settings['position'] ?? 'left') === 'right' ? 'right' : 'left';
@endphp

@if (($settings['type'] ?? 'light') === 'gray')
    <div class="main-banner">
        <div class="container">
            <div class="main-banner__wrap">
                @if (($settings['position'] ?? 'left') === 'right')
                    @include('modules.text-section.grey_text', ['fade' => 'right'])
                    @if ($hasImage)
                        @include('modules.text-section.grey_image', ['fade' => 'left'])
                    @endif
                @else
                    @if ($hasImage)
                        @include('modules.text-section.grey_image', ['fade' => 'right'])
                    @endif
                    @include('modules.text-section.grey_text', ['fade' => 'left'])
                @endif
            </div>
        </div>
    </div>
@else
    <div class="answers">
        <div class="container">
            <div class="answers__wrap">
                @if (($settings['position'] ?? 'left') === 'right')
                    @include('modules.text-section.light_text', ['fade' => 'right'])
                    @if ($hasImage)
                        @include('modules.text-section.light_image', ['fade' => 'left'])
                    @endif
                @else
                    @if ($hasImage)
                        @include('modules.text-section.light_image', ['fade' => 'right'])
                    @endif
                    @include('modules.text-section.light_text', ['fade' => 'left'])
                @endif
            </div>
        </div>
    </div>
@endif
