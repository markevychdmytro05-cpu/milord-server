<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can('access_manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('permission_names') === '' || $this->input('permission_names') === null) {
            $this->merge(['permission_names' => []]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:16', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('roles', 'name')->ignore($this->route('id'))],
            'permission_names' => ['present', 'array'],
            'permission_names.*' => ['string', 'distinct', Rule::exists('permissions', 'name')],
        ];
    }

    /** @return array<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->route('id')) {
                return;
            }
            $role = Role::findOrFail($this->route('id'));
            $user = User::findOrFail(backpack_user()->id);
            if (! $user->hasRole($role->name)) {
                return;
            }
            $keepsAccess = in_array('access_manage', $this->input('permission_names'), true)
                || $user->getDirectPermissions()->contains('name', 'access_manage');
            if (! $keepsAccess) {
                $validator->errors()->add('permission_names', 'Не можна забрати у своєї ролі право керування доступом без особистого дозволу.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'permission_names.*.exists' => 'Роль містить невідомий дозвіл.',
            'name.regex' => 'Код ролі: латинські малі літери, цифри, «_» або «-»; перший символ – літера.',
        ];
    }
}
