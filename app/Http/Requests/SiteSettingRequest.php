<?php

namespace App\Http\Requests;

use App\Rules\SafeUrl;
use App\Support\LocalUrl;
use Illuminate\Foundation\Http\FormRequest;

class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can('site_manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['logo_image' => LocalUrl::path($this->input('logo_image'))]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'header_logo' => ['required', 'string', 'max:60'],
            'footer_text' => ['required', 'string', 'max:255'],
            'logo_image' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
            'header_button_text' => ['nullable', 'string', 'max:40', 'required_with:header_button_url'],
            'header_button_url' => ['nullable', 'string', 'max:255', new SafeUrl, 'required_with:header_button_text'],
            'show_topbar' => ['boolean'],
            'footer_heading' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_email_label' => ['nullable', 'string', 'max:40'],
            'contact_email2' => ['nullable', 'email', 'max:120'],
            'contact_email2_label' => ['nullable', 'string', 'max:40'],
            'instagram' => ['nullable', 'string', 'max:255', 'regex:~^https://(www\.)?instagram\.com/.+~i'],
            'facebook' => ['nullable', 'string', 'max:255', 'regex:~^https://(www\.)?(facebook|fb)\.com/.+~i'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'contact_address' => ['nullable', 'string', 'max:200'],
            'telegram' => ['nullable', 'string', 'max:120', 'regex:~^(https?://(t|telegram)\.me/[A-Za-z0-9_+/-]+|(t\.me/)?@?[A-Za-z0-9_]{5,32})$~i'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'viber' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'footer_modules' => ['array'],
            'footer_modules.*' => ['integer', 'exists:modules,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'header_logo' => 'логотип',
            'footer_text' => 'текст підвалу',
        ];
    }
}
