<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('~^(/|#|https?://|mailto:|tel:)~i', $value)) {
            $fail('Поле :attribute має бути шляхом від кореня, якорем, http(s)-адресою, mailto: або tel:.');
        }
    }
}
