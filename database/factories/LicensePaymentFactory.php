<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\LicensePayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LicensePayment>
 */
class LicensePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'operation' => LicensePayment::OPERATION_PAYMENT,
            'amount_cents' => 10000,
            'currency' => 'USD',
            'paid_at' => now(),
            'idempotency_key' => (string) Str::uuid(),
            'request_hash' => hash('sha256', 'factory payment'),
        ];
    }
}
