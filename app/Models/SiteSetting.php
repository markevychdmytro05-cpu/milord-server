<?php

namespace App\Models;

use App\Models\Concerns\HasOrderedModules;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use CrudTrait, HasOrderedModules;

    protected $fillable = [
        'header_logo', 'logo_image', 'header_button_text', 'header_button_url',
        'footer_text', 'footer_heading', 'show_topbar',
        'contact_email', 'contact_email_label', 'contact_email2', 'contact_email2_label', 'contact_phone', 'contact_address',
        'telegram', 'whatsapp', 'viber', 'instagram', 'facebook', 'footer_modules',
    ];

    protected function casts(): array
    {
        return ['show_topbar' => 'boolean'];
    }

    protected function modulePivotTable(): string
    {
        return 'site_setting_module';
    }

    protected function modulePivotForeignKey(): string
    {
        return 'site_setting_id';
    }

    public function getFooterModulesAttribute(): array
    {
        return $this->orderedModuleIds();
    }

    public function setFooterModulesAttribute(?array $value): void
    {
        $this->queueModules($value);
    }

    public function hasTopbar(): bool
    {
        return $this->show_topbar && ($this->contact_email || $this->contact_phone || $this->contact_address);
    }

    public function hasFooterContacts(): bool
    {
        return $this->contact_email || $this->contact_email2 || $this->contact_phone || $this->contact_address || $this->messengers();
    }

    public function phoneUrl(): ?string
    {
        $digits = preg_replace('/[^0-9+]/', '', (string) $this->contact_phone);

        return $digits !== '' ? 'tel:'.$digits : null;
    }

    public function telegramUrl(): ?string
    {
        $value = trim((string) $this->telegram);

        if ($value === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        return 'https://t.me/'.ltrim(preg_replace('~^(?:t\.me/)~i', '', $value), '@');
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->whatsapp);

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

    public function viberUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->viber);

        return $digits !== '' ? 'viber://chat?number=%2B'.$digits : null;
    }

    public function messengers(): array
    {
        return array_filter([
            'Telegram' => $this->telegramUrl(),
            'WhatsApp' => $this->whatsappUrl(),
            'Viber' => $this->viberUrl(),
            'Instagram' => $this->instagram ?: null,
            'Facebook' => $this->facebook ?: null,
        ]);
    }

    public function organizationSchema(): array
    {
        $logo = $this->logo_image
            ? (str_starts_with($this->logo_image, 'http') ? $this->logo_image : url($this->logo_image))
            : url('/favicon-192.png');
        $profiles = array_values(array_filter($this->messengers(), fn (string $url): bool => str_starts_with($url, 'http')));
        $contact = array_filter([
            '@type' => 'ContactPoint',
            'contactType' => 'customer support',
            'email' => $this->contact_email,
            'telephone' => $this->contact_phone,
            'url' => $this->telegramUrl(),
            'availableLanguage' => 'uk',
        ]);

        return array_filter([
            '@type' => 'Organization',
            '@id' => url('/').'#org',
            'name' => $this->header_logo,
            'url' => url('/'),
            'logo' => $logo,
            'sameAs' => $profiles ?: null,
            'contactPoint' => count($contact) > 3 ? $contact : null,
        ]);
    }

    public function authorSchema(): array
    {
        return array_filter([
            '@type' => 'Person',
            '@id' => url('/').'#author',
            'name' => config('site.author_name'),
            'url' => url('/pro-nas'),
            'worksFor' => ['@id' => url('/').'#org'],
        ]);
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'header_logo' => 'Numis',
            'footer_text' => null,
        ]);
    }
}
