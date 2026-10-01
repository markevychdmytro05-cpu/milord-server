@if (backpack_user()->can('devices_unbind'))
    <form method="POST" action="{{ url($crud->route.'/'.$entry->getKey().'/reset-devices') }}" class="d-inline"
          onsubmit="return confirm('Відв\'язати всі пристрої від ключа {{ $entry->key }}?')">
        @csrf
        <button type="submit" class="btn btn-sm btn-link text-warning" title="Скинути пристрої">
            <i class="la la-unlink"></i> <span>Скинути пристрої</span>
        </button>
    </form>
@endif
