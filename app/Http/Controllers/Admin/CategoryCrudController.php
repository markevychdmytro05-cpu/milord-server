<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Validation\Rule;

/** @property-read CrudPanel $crud */
class CategoryCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(Category::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/category');
        CRUD::setEntityNameStrings('категорію', 'категорії');
        AdminPermissions::crud([
            'list' => 'pages_view', 'create' => 'pages_create',
            'update' => 'pages_update', 'delete' => 'pages_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('name');

        CRUD::column('name')->label('Назва');
        CRUD::column('pages_count')->type('closure')->label('Статей')->function(fn (Category $c) => $c->pagesCount());
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($this->crud->getCurrentEntryId())],
        ]);

        CRUD::field('name')->label('Назва')->hint('Після перейменування назва оновиться в усіх статтях категорії.');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function update()
    {
        $this->crud->getRequest()->merge(['id' => $this->crud->getRequest()->route('id')]);

        return $this->traitUpdate();
    }

    public function destroy($id)
    {
        CRUD::hasAccessOrFail('delete');

        if (Category::findOrFail($id)->pagesCount() > 0) {
            return response()->json(['message' => 'У категорії є статті. Спершу змініть їм категорію.'], 409);
        }

        return $this->traitDestroy($id);
    }
}
