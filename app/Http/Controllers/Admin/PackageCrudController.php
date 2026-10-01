<?php

namespace App\Http\Controllers\Admin;

use App\Models\Package;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/** @property-read CrudPanel $crud */
class PackageCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(Package::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/package');
        CRUD::setEntityNameStrings('пакет', 'пакети');
        AdminPermissions::crud([
            'list' => 'packages_view', 'create' => 'packages_create',
            'update' => 'packages_update', 'delete' => 'packages_delete',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('price');
        CRUD::addClause('withCount', 'licenses');

        CRUD::column('name')->label('Назва');
        CRUD::column('max_accounts')->type('number')->label('Акаунтів');
        CRUD::column('max_devices')->type('number')->label('Пристроїв');
        CRUD::column('duration_days')->type('closure')->label('Строк')
            ->function(fn (Package $p) => $p->durationLabel());
        CRUD::column('price')->type('number')->label('Ціна')->prefix('$');
        CRUD::column('licenses_count')->type('number')->label('Продано ключів');
        CRUD::column('is_active')->type('toggle')->label('У продажу')->toggle_route($this->crud->route);
        CRUD::column('show_on_home')->type('toggle')->label('На головній')->toggle_route($this->crud->route)->toggle_action('toggle-home');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => ['required', 'string', 'max:255'],
            'max_accounts' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_devices' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'is_active' => ['boolean'],
            'show_on_home' => ['boolean'],
        ]);

        CRUD::field('name')->label('Назва');
        CRUD::field('max_accounts')->type('number')->label('Кількість акаунтів')->attributes(['min' => 1])->default(5);
        CRUD::field('max_devices')->type('number')->label('Макс. пристроїв')->attributes(['min' => 1])->default(1);
        CRUD::field('price')->type('number')->label('Ціна, $')->prefix('$')->attributes(['min' => 0]);
        CRUD::field('duration_days')->type('number')->label('Строк, днів')->attributes(['min' => 1])->default(30)
            ->hint('Порожньо – безстроково.');
        CRUD::field('is_active')->type('switch')->label('У продажу')->default(true);
        CRUD::field('show_on_home')->type('switch')->label('Показувати на головній')->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        CRUD::field('name');
    }

    public function update()
    {
        $this->crud->getRequest()->merge(['id' => $this->crud->getRequest()->route('id')]);

        return $this->traitUpdate();
    }

    public function toggleActive(int $id)
    {
        return $this->toggleField($id, 'is_active');
    }

    public function toggleShowOnHome(int $id)
    {
        return $this->toggleField($id, 'show_on_home');
    }

    private function toggleField(int $id, string $field)
    {
        CRUD::hasAccessOrFail('update');

        $package = Package::findOrFail($id);
        $package->update([$field => ! $package->{$field}]);

        return response()->json(['value' => $package->{$field}]);
    }

    public function destroy($id)
    {
        CRUD::hasAccessOrFail('delete');

        if (Package::findOrFail($id)->licenses()->exists()) {
            return response()->json(['message' => 'За цим пакетом є видані ключі. Вимкніть його замість видалення.'], 409);
        }

        return $this->traitDestroy($id);
    }
}
