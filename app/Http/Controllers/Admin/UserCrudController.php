<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessManagement;
use App\Support\AdminPermissions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;

/** @property-read CrudPanel $crud */
class UserCrudController extends CrudController
{
    use CreateOperation { store as traitStore; }
    use DeleteOperation { destroy as traitDestroy; }
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup()
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/user');
        CRUD::setEntityNameStrings('користувача', 'користувачі');
        CRUD::with(['roles', 'permissions']);
        AdminPermissions::crud([
            'list' => 'users_view', 'create' => 'access_manage',
            'update' => 'access_manage', 'delete' => 'access_manage',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->label('Ім\'я');
        CRUD::column('email')->label('Email');
        CRUD::column('role_name')->type('closure')->label('Роль')->function(fn (User $user): string => $user->roles->first()?->title ?? '–');
        CRUD::column('permission_names')->type('closure')->label('Додаткові права')
            ->function(fn (User $user): int => $user->permissions->count());
        CRUD::column('is_active')->type('toggle')->label('Активний')->toggle_route($this->crud->route)
            ->toggle_disabled_for(backpack_user()->id);
        CRUD::column('created_at')->type('datetime')->label('Створено');
    }

    protected function setupCreateOperation()
    {
        $this->addFields(passwordRequired: true);
    }

    protected function setupUpdateOperation()
    {
        $this->addFields(passwordRequired: false);
    }

    public function store()
    {
        return AccessManagement::run(function () {
            Role::where('name', $this->crud->getRequest()->input('role_name'))->lockForUpdate()->first();

            return $this->syncAccess($this->traitStore());
        });
    }

    public function update()
    {
        $request = $this->crud->getRequest();
        $request->merge(['id' => $request->route('id')]);

        if (blank($request->input('password'))) {
            $request->request->remove('password');
            $request->request->remove('password_confirmation');
            $this->crud->removeField('password');
        }

        return AccessManagement::run(function (User $actor) use ($request) {
            if ((int) $request->route('id') === $actor->id) {
                $request->merge(['role_name' => $actor->role_name, 'is_active' => 1]);
            }
            Role::where('name', $request->input('role_name'))->lockForUpdate()->first();

            return $this->syncAccess($this->traitUpdate());
        });
    }

    private function syncAccess(mixed $response): mixed
    {
        $request = $this->crud->getRequest();
        $this->crud->entry->syncRoles($request->input('role_name'));
        if ($request->has('permission_names')) {
            $this->crud->entry->syncPermissions($request->input('permission_names', []));
        }

        return $response;
    }

    public function destroy($id)
    {
        CRUD::hasAccessOrFail('delete');
        abort_if((int) $id === backpack_user()->id, 403, 'Не можна видалити власний акаунт.');

        return AccessManagement::run(fn () => $this->traitDestroy($id));
    }

    public function toggleActive(int $id): JsonResponse
    {
        CRUD::hasAccessOrFail('update');

        return AccessManagement::run(function (User $actor) use ($id): JsonResponse {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if($user->is($actor), 403, 'Не можна вимкнути власний акаунт.');

            $user->update(['is_active' => ! $user->is_active]);

            return response()->json(['value' => $user->is_active]);
        });
    }

    private function addFields(bool $passwordRequired): void
    {
        CRUD::setValidation(UserRequest::class);
        $roles = Role::with('permissions')->orderBy('title')->get();

        CRUD::field('name')->label('Ім\'я');
        CRUD::field('email')->type('email')->label('Email');
        CRUD::field('role_name')->type('select_from_array')->label('Роль')
            ->options($roles->pluck('title', 'name')->all())->default(User::ROLE_MANAGER)->allows_null(false);
        CRUD::field('permission_names')->type('permission_picker')->label('Додаткові права')->default([])
            ->catalog(AdminPermissions::catalog())
            ->roles($roles->mapWithKeys(fn (Role $role): array => [$role->name => $role->permission_names])->all());
        CRUD::field('is_active')->type('switch')->label('Активний')->default(true);
        CRUD::field('password')->type('password')->label('Пароль')
            ->hint($passwordRequired ? 'Мін. 8 символів.' : 'Порожньо – не змінювати.');
        CRUD::field('password_confirmation')->type('password')->label('Повтор пароля');

        if ((int) $this->crud->getCurrentEntryId() === backpack_user()->id) {
            CRUD::field('role_name')->attributes(['disabled' => 'disabled']);
            CRUD::field('is_active')->attributes(['disabled' => 'disabled']);
        }
    }
}
