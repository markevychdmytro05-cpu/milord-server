<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Support\AccessManagement;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;

class RoleCrudController extends CrudController
{
    use CreateOperation { store as traitStore; }
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup(): void
    {
        abort_unless(backpack_user()?->can('access_manage'), 403);
        CRUD::setModel(Role::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/role');
        CRUD::setEntityNameStrings('роль', 'ролі');
    }

    protected function setupListOperation(): void
    {
        CRUD::addClause('withCount', ['users', 'permissions']);
        CRUD::column('title')->label('Роль');
        CRUD::column('name')->label('Код');
        CRUD::column('permissions_count')->type('number')->label('Прав');
        CRUD::column('users_count')->type('number')->label('Користувачів');
        CRUD::column('is_system')->type('boolean')->label('Стандартна');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(RoleRequest::class);
        CRUD::field('title')->label('Назва ролі');
        CRUD::field('name')->label('Код ролі')->attributes(['maxlength' => 16])
            ->hint('Не змінюється після створення.');
        CRUD::field('permission_names')->type('permission_picker')->label('Права ролі')->default([])
            ->catalog(AdminPermissions::catalog());
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
        CRUD::field('name')->attributes(['readonly' => 'readonly']);
    }

    public function update()
    {
        return AccessManagement::run(function () {
            $id = $this->crud->getRequest()->route('id');
            $role = Role::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->crud->getRequest()->merge(['id' => $id, 'name' => $role->name]);

            return $this->syncPermissions($this->traitUpdate());
        });
    }

    public function store()
    {
        return AccessManagement::run(fn () => $this->syncPermissions($this->traitStore()));
    }

    private function syncPermissions(mixed $response): mixed
    {
        $this->crud->entry->syncPermissions($this->crud->getRequest()->input('permission_names', []));

        return $response;
    }

    public function destroy(int|string $id): string|JsonResponse
    {
        CRUD::hasAccessOrFail('delete');

        return AccessManagement::run(function () use ($id): string|JsonResponse {
            $role = Role::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($role->is_system || $role->users()->exists()) {
                return response()->json(['message' => 'Стандартну роль або роль із користувачами не можна видалити. Спочатку змініть ролі користувачів.'], 409);
            }

            return $this->traitDestroy($id);
        });
    }
}
