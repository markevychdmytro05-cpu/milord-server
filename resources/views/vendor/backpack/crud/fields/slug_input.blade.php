{{-- Slug із кнопкою «Згенерувати» з поля-джерела ($field['source'], за замовчуванням title). --}}
@php
    $value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
    $id = 'slug-input-'.\Illuminate\Support\Str::slug($field['name']);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>

    <div id="{{ $id }}" data-source="{{ $field['source'] ?? 'title' }}">
        <div class="input-group">
            <input type="text" class="form-control slug-value" name="{{ $field['name'] }}" value="{{ $value }}">
            <button type="button" class="btn btn-success slug-generate"><i class="la la-magic"></i> Згенерувати</button>
        </div>
    </div>
@include('crud::fields.inc.wrapper_end')

<script>
    (function () {
        var root = document.getElementById(@json($id));
        var input = root.querySelector('.slug-value');
        var map = {
            'а':'a','б':'b','в':'v','г':'h','ґ':'g','д':'d','е':'e','є':'ie','ж':'zh','з':'z','и':'y','і':'i','ї':'i','й':'i',
            'к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch',
            'ш':'sh','щ':'shch','ь':'','ю':'iu','я':'ia','ъ':'','ы':'y','э':'e','ё':'io'
        };

        function slugify(text) {
            return text.toLowerCase().split('').map(function (ch) { return map.hasOwnProperty(ch) ? map[ch] : ch; }).join('')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }

        root.querySelector('.slug-generate').addEventListener('click', function () {
            var source = document.querySelector('[name="' + root.dataset.source + '"]');
            if (source) { input.value = slugify(source.value); }
        });
    })();
</script>
