<?php

namespace App\Http\Requests;

use App\Models\LicensePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LicensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can('payments_create') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'operation' => ['required', Rule::in([LicensePayment::OPERATION_PAYMENT, LicensePayment::OPERATION_RENEWAL])],
            'amount' => ['required', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/', 'numeric', 'gt:0'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'duration_days' => ['exclude_unless:operation,renewal', 'required', 'integer', 'min:1', 'max:3650'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('amount'))) {
            $this->merge(['amount' => str_replace(',', '.', trim($this->input('amount')))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.regex' => 'Вкажіть суму до 99 999 999.99 USD, не більше двох знаків після крапки.',
            'amount.gt' => 'Сума оплати має бути більшою за нуль.',
            'paid_at.before_or_equal' => 'Дата оплати не може бути в майбутньому.',
            'duration_days.required' => 'Вкажіть кількість днів продовження.',
        ];
    }

    public function amountInCents(): int
    {
        [$dollars, $cents] = array_pad(explode('.', $this->validated('amount')), 2, '0');

        return (int) $dollars * 100 + (int) str_pad($cents, 2, '0');
    }
}
