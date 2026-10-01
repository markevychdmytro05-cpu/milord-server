<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can('access_manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('permission_names') && blank($this->input('permission_names'))) {
            $this->merge(['permission_names' => []]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('id'))],
            'role_name' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['boolean'],
            'password' => [$this->route('id') ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', 'distinct', Rule::exists('permissions', 'name')],
        ];
    }

    /** @return array<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || (int) $this->route('id') !== backpack_user()->id) {
                return;
            }
            $user = User::findOrFail($this->route('id'));
            $extra = $this->has('permission_names') ? $this->input('permission_names') : $user->permissions->pluck('name')->all();
            $keepsAccess = in_array('access_manage', $extra, true)
                || $user->roles->contains(fn ($role): bool => $role->permissions->contains('name', 'access_manage'));
            if (! $keepsAccess) {
                $validator->errors()->add('permission_names', 'Не можна забрати у себе право керування доступом.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'role_name.exists' => 'Оберіть наявну роль.',
            'permission_names.*.exists' => 'Особисті права містять невідомий дозвіл.',
        ];
    }
}
