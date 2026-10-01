{{-- Впорядкований вибір: значення надсилаються масивом у порядку списку. --}}
@php
    $selected = array_map('intval', (array) (old_empty_or_null($field['name'], null) ?? $field['value'] ?? $field['default'] ?? []));
    $options = $field['options'] ?? [];
    $id = 'select-and-order-'.\Illuminate\Support\Str::slug($field['name']);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>

    <div id="{{ $id }}" data-name="{{ $field['name'] }}[]" data-options='@json($options)' data-edit-url="{{ backpack_url('module') }}">
        <ul class="list-group mb-2 picker-list">
            @foreach ($selected as $optionId)
                @if (isset($options[$optionId]))
                    <li class="list-group-item d-flex align-items-center gap-2" data-id="{{ $optionId }}" draggable="true">
                        <span class="picker-handle text-muted" title="Перетягнути" style="cursor:grab;margin-right:14px">&#8942;&#8942;</span>
                        <input type="hidden" name="{{ $field['name'] }}[]" value="{{ $optionId }}">
                        <a class="flex-grow-1" href="{{ backpack_url('module/'.$optionId.'/edit') }}" target="_blank">{{ $options[$optionId] }}</a>
                        <button type="button" class="btn btn-sm btn-outline-secondary picker-up" title="Вище">&uarr;</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary picker-down" title="Нижче">&darr;</button>
                        <button type="button" class="btn btn-sm btn-outline-danger picker-remove" title="Прибрати">&times;</button>
                    </li>
                @endif
            @endforeach
        </ul>
        <div class="d-flex gap-2">
            <select class="form-control picker-select">
                <option value="">– обрати модуль –</option>
                @foreach ($options as $optionId => $label)
                    <option value="{{ $optionId }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-secondary picker-add">Додати</button>
        </div>
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

<script>
    (function () {
        var root = document.getElementById(@json($id));
        var list = root.querySelector('.picker-list');
        var select = root.querySelector('.picker-select');
        var options = JSON.parse(root.dataset.options);
        var name = root.dataset.name;
        var editUrl = root.dataset.editUrl;

        function has(id) { return !!list.querySelector('li[data-id="' + id + '"]'); }

        function refresh() {
            Array.prototype.forEach.call(select.options, function (option) {
                option.disabled = option.value !== '' && has(option.value);
            });
        }

        root.querySelector('.picker-add').addEventListener('click', function () {
            var id = select.value;
            if (!id || has(id)) { return; }
            var item = document.createElement('li');
            item.className = 'list-group-item d-flex align-items-center gap-2';
            item.dataset.id = id;
            item.draggable = true;
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = id;
            var link = document.createElement('a');
            link.className = 'flex-grow-1'; link.href = editUrl + '/' + id + '/edit'; link.target = '_blank'; link.textContent = options[id];
            var handle = document.createElement('span');
            handle.className = 'picker-handle text-muted'; handle.title = 'Перетягнути'; handle.style.cursor = 'grab'; handle.style.marginRight = '14px'; handle.innerHTML = '&#8942;&#8942;';
            item.appendChild(handle);
            item.appendChild(input);
            item.appendChild(link);
            item.insertAdjacentHTML('beforeend',
                '<button type="button" class="btn btn-sm btn-outline-secondary picker-up" title="Вище">&uarr;</button> '
                + '<button type="button" class="btn btn-sm btn-outline-secondary picker-down" title="Нижче">&darr;</button> '
                + '<button type="button" class="btn btn-sm btn-outline-danger picker-remove" title="Прибрати">&times;</button>');
            list.appendChild(item);
            select.value = '';
            refresh();
        });

        list.addEventListener('click', function (event) {
            var item = event.target.closest('li');
            if (!item) { return; }
            if (event.target.closest('.picker-remove')) { item.remove(); }
            if (event.target.closest('.picker-up') && item.previousElementSibling) { list.insertBefore(item, item.previousElementSibling); }
            if (event.target.closest('.picker-down') && item.nextElementSibling) { list.insertBefore(item.nextElementSibling, item); }
            refresh();
        });

        var dragged = null;

        list.addEventListener('dragstart', function (event) {
            dragged = event.target.closest('li');
            if (!dragged) { return; }
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragged.dataset.id);
            dragged.style.opacity = '.4';
        });

        list.addEventListener('dragover', function (event) {
            if (!dragged) { return; }
            event.preventDefault();
            var after = Array.prototype.filter.call(list.children, function (item) { return item !== dragged; })
                .find(function (item) {
                    var box = item.getBoundingClientRect();
                    return event.clientY < box.top + box.height / 2;
                });
            if (after) { list.insertBefore(dragged, after); } else { list.appendChild(dragged); }
        });

        list.addEventListener('drop', function (event) { event.preventDefault(); });

        list.addEventListener('dragend', function () {
            if (dragged) { dragged.style.opacity = ''; }
            dragged = null;
        });

        refresh();
    })();
</script>
