{{--
    Перемикач прямо в таблиці. Потрібно: $column['toggle_route'] – URL-префікс, до якого додається /{id}/toggle.
    Сервер повертає {"value": bool}; при помилці перемикач повертається назад.
--}}
@php
    $checked = (bool) data_get($entry, $column['name']);
    $switchId = 'toggle-'.$column['name'].'-'.$entry->getKey();
    $disabled = ! $crud->hasAccess('update') || (isset($column['toggle_disabled_for']) && (int) $column['toggle_disabled_for'] === (int) $entry->getKey());
@endphp
<span class="custom-control custom-switch form-check form-switch d-inline-block mb-0 toggle-lg">
    <input type="checkbox" class="custom-control-input form-check-input" id="{{ $switchId }}" @checked($checked) @disabled($disabled)
           data-url="{{ url($column['toggle_route'].'/'.$entry->getKey().'/'.($column['toggle_action'] ?? 'toggle')) }}"
           onchange="(function (el) {
               el.disabled = true;
               fetch(el.dataset.url, {
                   method: 'POST',
                   headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'},
               })
                   .then(function (r) { if (!r.ok) { throw r; } return r.json(); })
                   .then(function (data) { el.checked = data.value; })
                   .catch(function () {
                       el.checked = !el.checked;
                       new Noty({type: 'error', text: 'Не вдалося зберегти зміну'}).show();
                   })
                   .finally(function () { el.disabled = false; });
           })(this)">
    <label class="custom-control-label form-check-label" for="{{ $switchId }}"></label>
</span>
