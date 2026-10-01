{{-- Список записів із підполями text/textarea; надсилається як name[i][підполе]. --}}
@php
    $subfields = $field['subfields'] ?? [];
    $raw = old_empty_or_null($field['name'], null) ?? $field['value'] ?? $field['default'] ?? [];
    $rows = array_values(is_string($raw) ? (json_decode($raw, true) ?: []) : (array) $raw);
    $id = 'simple-repeatable-'.\Illuminate\Support\Str::slug($field['name']);
    $renderInput = function (array $sub, string $inputName, string $value) {
        $attrs = 'class="form-control" placeholder="'.e($sub['label'] ?? $sub['name']).'"';
        if (($sub['type'] ?? 'text') === 'select') {
            $options = collect($sub['options'] ?? [])->map(fn ($label, $key) => '<option value="'.e($key).'"'.((string) $key === $value ? ' selected' : '').'>'.e($label).'</option>')->implode('');
            return '<select class="form-control" name="'.e($inputName).'" title="'.e($sub['label'] ?? $sub['name']).'">'.$options.'</select>';
        }
        return ($sub['type'] ?? 'text') === 'textarea'
            ? '<textarea '.$attrs.' rows="3" name="'.e($inputName).'">'.e($value).'</textarea>'
            : '<input type="text" '.$attrs.' name="'.e($inputName).'" value="'.e($value).'">';
    };
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>

    <div id="{{ $id }}" data-name="{{ $field['name'] }}" data-subfields='@json($subfields)'>
        <div class="repeat-rows">
            @foreach ($rows as $i => $row)
                <div class="card card-body mb-2 repeat-row">
                    @foreach ($subfields as $sub)
                        <div class="mb-2">{!! $renderInput($sub, $field['name'].'['.$i.']['.$sub['name'].']', (string) ($row[$sub['name']] ?? '')) !!}</div>
                    @endforeach
                    <button type="button" class="btn btn-sm btn-outline-danger repeat-remove align-self-start">Видалити</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary repeat-add">+ Додати</button>
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

<script>
    (function () {
        var root = document.getElementById(@json($id));
        var rows = root.querySelector('.repeat-rows');
        var name = root.dataset.name;
        var subfields = JSON.parse(root.dataset.subfields);

        function reindex() {
            Array.prototype.forEach.call(rows.querySelectorAll('.repeat-row'), function (row, i) {
                Array.prototype.forEach.call(row.querySelectorAll('input, textarea, select'), function (input, j) {
                    input.name = name + '[' + i + '][' + subfields[j].name + ']';
                });
            });
        }

        root.querySelector('.repeat-add').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'card card-body mb-2 repeat-row';
            subfields.forEach(function (sub) {
                var wrap = document.createElement('div');
                wrap.className = 'mb-2';
                var input = document.createElement(sub.type === 'textarea' ? 'textarea' : (sub.type === 'select' ? 'select' : 'input'));
                input.className = 'form-control';
                input.placeholder = sub.label || sub.name;
                if (sub.type === 'textarea') { input.rows = 3; }
                else if (sub.type === 'select') {
                    Object.keys(sub.options || {}).forEach(function (key) {
                        var option = document.createElement('option');
                        option.value = key; option.textContent = sub.options[key];
                        input.appendChild(option);
                    });
                } else { input.type = 'text'; }
                wrap.appendChild(input);
                row.appendChild(wrap);
            });
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger repeat-remove align-self-start';
            remove.textContent = 'Видалити';
            row.appendChild(remove);
            rows.appendChild(row);
            reindex();
        });

        root.addEventListener('click', function (event) {
            var button = event.target.closest('.repeat-remove');
            if (button) {
                button.closest('.repeat-row').remove();
                reindex();
            }
        });
    })();
</script>
