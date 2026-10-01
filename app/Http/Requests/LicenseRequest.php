<?php

namespace App\Http\Requests;

use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->route('id') ? 'licenses_update' : 'licenses_create') ?? false;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', Rule::exists('packages', 'id')->where(
                fn ($query) => $query->where('is_active', true)->orWhere('id', $this->route('id') ? License::find($this->route('id'))?->package_id : null)
            )],
            'customer' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in([License::STATUS_ACTIVE, License::STATUS_REVOKED])],
            'expires_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
