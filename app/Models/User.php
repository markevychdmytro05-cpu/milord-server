<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use CrudTrait, HasFactory, Notifiable;

    use HasRoles {
        checkPermissionTo as private checkSpatiePermissionTo;
    }

    protected $guard_name = 'web';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /** @return list<string> */
    public function getPermissionNamesAttribute(): array
    {
        return $this->permissions->pluck('name')->all();
    }

    /**
     * Вимкнений користувач або користувач без ролі не має жодних прав, навіть особистих.
     */
    public function checkPermissionTo($permission, ?string $guardName = null): bool
    {
        return $this->is_active && $this->roles->isNotEmpty() && $this->checkSpatiePermissionTo($permission, $guardName);
    }
}
