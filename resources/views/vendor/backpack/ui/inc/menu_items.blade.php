{{-- This file is used for menu items by any Backpack v7 theme --}}
@if (backpack_user()->can('dashboard_view'))
    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
@endif

@if (backpack_user()->can('licenses_view'))
    <x-backpack::menu-item title="Ліцензії" icon="la la-key" :link="backpack_url('license')" />
@elseif (backpack_user()->can('licenses_create'))
    <x-backpack::menu-item title="Створити ліцензію" icon="la la-key" :link="backpack_url('license/create')" />
@endif
@if (backpack_user()->can('devices_view'))
    <x-backpack::menu-item title="Активації" icon="la la-laptop" :link="backpack_url('license-activation')" />
@endif
@if (backpack_user()->can('audit_view'))
    <x-backpack::menu-item title="Журнал дій" icon="la la-history" :link="backpack_url('license-audit')" />
@endif
@if (backpack_user()->can('packages_view'))
    <x-backpack::menu-item title="Пакети" icon="la la-box" :link="backpack_url('package')" />
@elseif (backpack_user()->can('packages_create'))
    <x-backpack::menu-item title="Створити пакет" icon="la la-box" :link="backpack_url('package/create')" />
@endif
@if (backpack_user()->can('users_view'))
    <x-backpack::menu-item title="Користувачі" icon="la la-users" :link="backpack_url('user')" />
@elseif (backpack_user()->can('access_manage'))
    <x-backpack::menu-item title="Створити користувача" icon="la la-user-plus" :link="backpack_url('user/create')" />
@endif
@if (backpack_user()->can('access_manage'))
    <x-backpack::menu-item title="Ролі та права" icon="la la-user-shield" :link="backpack_url('role')" />
@endif
@if (backpack_user()->can('leads_view'))
    <x-backpack::menu-item title="Заявки" icon="la la-inbox" :link="backpack_url('lead')" />
@endif
@if (backpack_user()->canany(['site_manage', 'pages_view', 'pages_create', 'coins_view', 'coins_create', 'translations_view', 'modules_view', 'modules_create']))
    <x-backpack::menu-dropdown title="Вміст сайту" icon="la la-globe">
        @if (backpack_user()->can('pages_view'))
            <x-backpack::menu-dropdown-item title="Сторінки" icon="la la-file-alt" :link="backpack_url('page')" />
        @elseif (backpack_user()->can('pages_create'))
            <x-backpack::menu-dropdown-item title="Створити сторінку" icon="la la-file-alt" :link="backpack_url('page/create')" />
        @endif
        @if (backpack_user()->can('pages_view'))
            <x-backpack::menu-dropdown-item title="Категорії" icon="la la-tags" :link="backpack_url('category')" />
        @endif
        @if (backpack_user()->can('coins_view'))
            <x-backpack::menu-dropdown-item title="Монети" icon="la la-coins" :link="backpack_url('coin')" />
        @elseif (backpack_user()->can('coins_create'))
            <x-backpack::menu-dropdown-item title="Створити монету" icon="la la-coins" :link="backpack_url('coin/create')" />
        @endif
        @if (backpack_user()->can('translations_view'))
            <x-backpack::menu-dropdown-item title="Переклади" icon="la la-language" :link="backpack_url('translation')" />
        @endif
        @if (backpack_user()->can('modules_view'))
            <x-backpack::menu-dropdown-item title="Модулі" icon="la la-th-large" :link="backpack_url('module')" />
        @elseif (backpack_user()->can('modules_create'))
            <x-backpack::menu-dropdown-item title="Створити модуль" icon="la la-th-large" :link="backpack_url('module/create')" />
        @endif
        @if (\App\Support\MediaAccess::allowed())
            <x-backpack::menu-dropdown-item title="Файли" icon="la la-images" :link="backpack_url('elfinder')" />
        @endif
        @if (backpack_user()->can('site_manage'))
            <x-backpack::menu-dropdown title="Меню" icon="la la-bars" :nested="true">
                <x-backpack::menu-dropdown-item title="Меню шапки" :link="backpack_url('header-menu')" />
                <x-backpack::menu-dropdown-item title="Меню підвалу" :link="backpack_url('footer-menu')" />
            </x-backpack::menu-dropdown>
            <x-backpack::menu-dropdown-item title="Шапка й підвал" icon="la la-columns" :link="backpack_url('site-setting/'.\App\Models\SiteSetting::current()->id.'/edit')" />
        @endif
    </x-backpack::menu-dropdown>
@endif
