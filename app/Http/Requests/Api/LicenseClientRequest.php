<?php

namespace App\Http\Requests\Api;

use App\Models\Release;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LicenseClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:64'],
            'device_id' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'device_name' => ['nullable', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'app_version' => ['nullable', 'string', 'max:32', 'not_regex:/[\r\n]/'],
            'platform' => ['nullable', 'string', Rule::in(array_keys(Release::PLATFORMS))],
            'arch' => ['nullable', 'string', Rule::in(array_keys(Release::ARCHES))],
            'accounts_used' => ['prohibited'],
            'nonce' => ['required', 'string', 'regex:/^[a-f0-9]{48}$/'],
            'request_time' => ['required', 'integer', 'min:1'],
            'device_public_key' => ['required', 'string', 'size:44'],
            'device_signature' => ['required', 'string', 'size:88'],
        ];
    }
}
