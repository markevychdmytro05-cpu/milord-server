<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /** Роль за замовчуванням – менеджер, як і раніше. */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(User::ROLE_MANAGER);
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles(User::ROLE_ADMIN));
    }

    public function withoutRole(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles([]));
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles($role));
    }

    /** @param  list<string>  $permissions */
    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(fn (User $user) => $user->givePermissionTo($permissions));
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
