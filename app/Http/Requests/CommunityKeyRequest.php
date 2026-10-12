<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommunityKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['key' => ['required', 'string', 'min:8', 'max:64']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['key.required' => __('community.key.required')];
    }
}
