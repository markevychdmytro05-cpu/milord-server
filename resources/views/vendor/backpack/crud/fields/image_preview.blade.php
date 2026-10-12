{{-- Попередній перегляд зображення з іншого поля форми ($field['source'] – ім’я поля з шляхом до файлу); оновлюється під час вибору. --}}
@php
    $id = 'image-preview-'.\Illuminate\Support\Str::slug($field['name']);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] ?? '' !!}</label>

    <div id="{{ $id }}" data-source="{{ $field['source'] ?? 'image' }}" style="display:flex;align-items:center;justify-content:center;width:220px;height:220px;border:1px dashed #cfd3e6;border-radius:12px;background:#f5f6fd;overflow:hidden">
        <img alt="" style="display:none;max-width:100%;max-height:100%;object-fit:contain">
        <span class="text-muted">Фото не вибрано</span>
    </div>
@include('crud::fields.inc.wrapper_end')

<script>
    (function () {
        var root = document.getElementById(@json($id));
        var input = null;
        var img = root.querySelector('img');
        var empty = root.querySelector('span');
        var shown = null;

        function sync() {
            if (!input) {
                input = document.querySelector('[name="' + root.dataset.source + '"]');
                if (input) { ['input', 'change'].forEach(function (name) { input.addEventListener(name, sync); }); }
            }
            var value = input ? input.value.trim() : '';
            if (value === shown) { return; }
            shown = value;
            img.style.display = value ? 'block' : 'none';
            empty.style.display = value ? 'none' : 'inline';
            if (value) { img.src = value; }
        }

        sync();
        setInterval(sync, 500);
    })();
</script>
