<div class="cm-badges">
    <span class="cm-typebox cm-typebox--{{ $topic->type }}">@include('community._icon', ['name' => $topic->type])</span>
    <span class="cm-badge cm-badge--{{ $topic->type }}">@include('community._icon', ['name' => $topic->type]){{ __('community.type_singular.'.$topic->type) }}</span>
    @if ($topic->is_pinned)<span class="cm-badge">{{ __('community.pinned') }}</span>@endif
    @if ($topic->is_members_only)<span class="cm-badge">@include('community._icon', ['name' => 'lock']){{ __('community.members') }}</span>@endif
    @if ($topic->is_staff)<span class="cm-badge cm-badge--staff">{{ __('community.staff') }}</span>@endif
</div>
