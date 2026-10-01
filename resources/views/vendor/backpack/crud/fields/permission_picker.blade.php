@php
    $selected = old($field['name'], $field['value'] ?? $field['default'] ?? []);
    $selected = is_array($selected) ? array_values(array_filter($selected, 'is_string')) : [];
    $catalog = $field['catalog'] ?? [];
    $pickerId = 'permission-picker-'.sha1($field['name']);
    $renderItem = function (string $name, string $group, string $title) {
        return '<li class="permission-picker__item" draggable="true" tabindex="0" data-name="'.e($name).'" data-group="'.e($group).'" title="Перетягніть або натисніть Enter">'
            .'<small class="text-muted">'.e($group).'</small> '.e($title).'</li>';
    };
@endphp
@include('crud::fields.inc.wrapper_start')
    <label>{{ $field['label'] }}</label>
    <input type="hidden" name="{{ $field['name'] }}" value="">
    <div class="permission-picker row" id="{{ $pickerId }}" data-field="{{ $field['name'] }}" data-roles="{{ json_encode($field['roles'] ?? []) }}">
        <div class="col-md-6 mb-2">
            <div class="fw-bold mb-1">Доступні</div>
            <ul class="permission-picker__list" data-list="available">
                @foreach ($catalog as $group => $permissions)
                    @foreach ($permissions as $name => $title)
                        @unless (in_array($name, $selected, true))
                            {!! $renderItem($name, $group, $title) !!}
                        @endunless
                    @endforeach
                @endforeach
            </ul>
        </div>
        <div class="col-md-6 mb-2">
            <div class="fw-bold mb-1">Призначені</div>
            @if (isset($field['roles']))
                <div class="small text-muted mb-1" data-inherited></div>
            @endif
            <ul class="permission-picker__list" data-list="assigned">
                @foreach ($catalog as $group => $permissions)
                    @foreach ($permissions as $name => $title)
                        @if (in_array($name, $selected, true))
                            {!! $renderItem($name, $group, $title) !!}
                        @endif
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
    <div data-inputs></div>
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')
@loadOnce('permission_picker_assets')
@push('crud_fields_styles')
<style>
    .permission-picker__list { min-height: 8rem; max-height: 22rem; overflow-y: auto; margin: 0; padding: .5rem; list-style: none; border: 1px dashed var(--tblr-border-color, #ccc); border-radius: .375rem; }
    .permission-picker__list.is-over { background: rgba(32, 107, 196, .08); border-color: #206bc4; }
    .permission-picker__item { padding: .35rem .6rem; margin-bottom: .35rem; background: var(--tblr-bg-surface, #fff); border: 1px solid var(--tblr-border-color, #ddd); border-radius: .25rem; cursor: grab; user-select: none; }
    .permission-picker__item.is-dragging { opacity: .4; }
</style>
@endpush
@push('crud_fields_scripts')
<script>
    document.querySelectorAll('.permission-picker').forEach(function (picker) {
        if (picker.dataset.ready) return;
        picker.dataset.ready = '1';
        const form = picker.closest('form');
        const available = picker.querySelector('[data-list=available]');
        const assigned = picker.querySelector('[data-list=assigned]');
        const inputs = picker.parentElement.querySelector('[data-inputs]');
        const inherited = picker.querySelector('[data-inherited]');
        const roles = JSON.parse(picker.dataset.roles);
        const roleSelect = form.querySelector('select[name=role_name]');
        let dragged = null;

        function sync() {
            inputs.innerHTML = '';
            assigned.querySelectorAll('.permission-picker__item').forEach(function (item) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = picker.dataset.field + '[]';
                input.value = item.dataset.name;
                inputs.appendChild(input);
            });
            if (inherited && roleSelect) {
                const names = roles[roleSelect.value] || [];
                inherited.textContent = names.length ? 'Ще ' + names.length + ' прав дає роль.' : '';
            }
        }
        function move(item) {
            (item.parentElement === assigned ? available : assigned).appendChild(item);
            sync();
        }

        picker.addEventListener('dragstart', function (event) {
            dragged = event.target.closest('.permission-picker__item');
            if (!dragged) return;
            dragged.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragged.dataset.name);
        });
        picker.addEventListener('dragend', function () {
            if (dragged) dragged.classList.remove('is-dragging');
            picker.querySelectorAll('.is-over').forEach(function (list) { list.classList.remove('is-over'); });
            dragged = null;
        });
        [available, assigned].forEach(function (list) {
            list.addEventListener('dragover', function (event) { if (dragged) { event.preventDefault(); list.classList.add('is-over'); } });
            list.addEventListener('dragleave', function () { list.classList.remove('is-over'); });
            list.addEventListener('drop', function (event) {
                event.preventDefault();
                list.classList.remove('is-over');
                if (dragged && dragged.parentElement !== list) { list.appendChild(dragged); sync(); }
            });
        });
        picker.addEventListener('dblclick', function (event) {
            const item = event.target.closest('.permission-picker__item');
            if (item) move(item);
        });
        picker.addEventListener('keydown', function (event) {
            const item = event.target.closest('.permission-picker__item');
            if (item && event.key === 'Enter') { event.preventDefault(); move(item); item.focus(); }
        });
        if (roleSelect) roleSelect.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
@endLoadOnce
