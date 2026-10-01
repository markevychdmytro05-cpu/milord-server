<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MenuItemRequest;
use App\Models\MenuItem;
use App\Models\Page;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Основа керування меню: пункти впорядковуються перетягуванням, вкладеність – до двох рівнів.
 *
 * @property-read CrudPanel $crud
 */
abstract class MenuCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ReorderOperation;
    use UpdateOperation { update as traitUpdate; }

    /** @return array{0: string, 1: string, 2: string} menu, route, назва */
    abstract protected function menu(): array;

    public function setup()
    {
        [$menu, $route, $name] = $this->menu();

        CRUD::setModel(MenuItem::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/'.$route);
        CRUD::setEntityNameStrings('пункт меню', $name);
        CRUD::addClause('where', 'menu', $menu);
        AdminPermissions::crud([
            'list' => 'site_manage', 'create' => 'site_manage', 'update' => 'site_manage',
            'delete' => 'site_manage', 'reorder' => 'site_manage',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('lft');

        CRUD::column('label')->label('Пункт');
        CRUD::column('target')->type('closure')->label('Куди веде')
            ->function(fn (MenuItem $item) => $item->page ? '/'.ltrim($item->page->slug === Page::ROOT_SLUG ? '' : $item->page->slug, '/') : $item->url);
        CRUD::column('is_active')->type('toggle')->label('Активний')->toggle_route($this->crud->route);
    }

    protected function setupReorderOperation()
    {
        CRUD::set('reorder.label', 'label');
        CRUD::set('reorder.max_level', 2);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(MenuItemRequest::class);

        CRUD::field('menu')->type('hidden')->default($this->menu()[0]);
        CRUD::field('page_id')->type('select_from_array')->label('Сторінка')
            ->options(Page::query()->orderBy('title')->pluck('title', 'id')->all())->allows_null(true);
        CRUD::field('url')->label('Або адреса (https://…, /#якір, mailto:…)');
        CRUD::field('title')->label('Назва')->wrapper(['class' => 'form-group col-12']);
        CRUD::field('open_in_new_tab')->type('switch')->label('Відкривати в новій вкладці');
        CRUD::field('is_active')->type('switch')->label('Активний')->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function toggleActive(int $id)
    {
        CRUD::hasAccessOrFail('update');

        $item = MenuItem::where('menu', $this->menu()[0])->findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        return response()->json(['value' => $item->is_active]);
    }

    public function update()
    {
        $this->crud->getRequest()->merge(['id' => $this->crud->getRequest()->route('id')]);

        return $this->traitUpdate();
    }
}
