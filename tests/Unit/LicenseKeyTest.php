<?php

namespace Tests\Unit;

use App\Models\License;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LicenseKeyTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function keyVariants(): array
    {
        return [
            'canonical' => ['ABCD-EFGH-JKLM-NPQR'],
            'lowercase' => ['abcd-efgh-jklm-npqr'],
            'no dashes' => ['ABCDEFGHJKLMNPQR'],
            'spaces around and inside' => ['  abcd efgh jklm npqr '],
        ];
    }

    #[DataProvider('keyVariants')]
    public function test_key_input_is_normalized_to_canonical_form(string $input): void
    {
        $this->assertSame('ABCD-EFGH-JKLM-NPQR', License::normalizeKey($input));
    }
}
