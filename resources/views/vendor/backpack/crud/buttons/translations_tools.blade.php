@if (backpack_user()->can('translations_update'))
    <form method="POST" action="{{ url($crud->route.'/sync') }}" class="d-inline"
          onsubmit="return confirm('Додати в базу ключі з файлів lang/? Наявні правки не зміняться.')">
        @csrf
        <button type="submit" class="btn btn-outline-primary"><i class="la la-sync"></i> Синхронізувати з файлами</button>
    </form>
    <form method="POST" action="{{ url($crud->route.'/export') }}" class="d-inline"
          onsubmit="return confirm('Записати переклади з бази у файли lang/? Файли будуть перезаписані.')">
        @csrf
        <button type="submit" class="btn btn-outline-secondary"><i class="la la-file-export"></i> Записати у файли</button>
    </form>
@endif
