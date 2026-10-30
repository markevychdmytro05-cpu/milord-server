<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('role_????????'),
            'title' => fake()->words(2, true),
            'is_system' => false,
        ];
    }

    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(fn (Role $role) => $role->givePermissionTo($permissions));
    }
}
