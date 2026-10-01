<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'contact' => ['required', 'string', 'min:5', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:2048'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->sourceUrl() ?? url('/');
    }

    /** Адреса сторінки, з якої надіслано форму (лише власний сайт). */
    public function sourceUrl(): ?string
    {
        $url = (string) $this->input('source');
        $origin = parse_url(url('/'));
        $source = parse_url($url);

        if (mb_strlen($url) > 2048 || ! is_array($origin) || ! is_array($source)) {
            return null;
        }

        if (array_key_exists('user', $source) || array_key_exists('pass', $source)) {
            return null;
        }

        return strtolower($source['scheme'] ?? '') === strtolower($origin['scheme'] ?? '')
            && strtolower($source['host'] ?? '') === strtolower($origin['host'] ?? '')
            && ($source['port'] ?? null) === ($origin['port'] ?? null)
                ? $url
                : null;
    }
}
