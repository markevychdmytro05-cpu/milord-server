<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Carbon;

final readonly class LicenseCheckResult
{
    private function __construct(
        public int $status,
        public ?string $error,
        public ?License $license,
        public ?Carbon $validUntil = null,
    ) {}

    public static function ok(License $license, Carbon $validUntil): self
    {
        return new self(200, null, $license, $validUntil);
    }

    public static function fail(string $error, int $status, ?License $license = null): self
    {
        return new self($status, $error, $license);
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }
}
