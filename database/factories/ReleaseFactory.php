<?php

namespace Database\Factories;

use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReleaseFactory extends Factory
{
    public function definition(): array
    {
        $version = '1.0.'.fake()->unique()->numberBetween(0, 9999);

        return [
            'version' => $version,
            'platform' => 'win',
            'arch' => 'x64',
            'file_path' => 'releases/'.$version.'/nbu-desktop-'.$version.'-win-x64.exe',
            'file_name' => 'nbu-desktop-'.$version.'-win-x64.exe',
            'size' => 1048576,
            'sha256' => hash('sha256', $version),
            'notes' => 'Виправлення помилок.',
            'is_active' => true,
            'published_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function platform(string $platform, string $arch = 'x64'): static
    {
        return $this->state(fn (array $attributes) => ['platform' => $platform, 'arch' => $arch]);
    }
}
