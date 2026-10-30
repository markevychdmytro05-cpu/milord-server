<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use CrudTrait, HasFactory;

    protected $guard_name = 'web';

    protected $fillable = ['name', 'title'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function getPermissionNamesAttribute(): array
    {
        return $this->permissions->pluck('name')->all();
    }
}
