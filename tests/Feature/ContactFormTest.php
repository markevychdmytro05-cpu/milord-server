<?php

namespace Tests\Feature;

use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use App\Models\Module;
use App\Models\Package;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + ['name' => 'Іван', 'contact' => '+380501112233', 'message' => 'Хочу тариф Про', 'source' => url('/')];
    }

    public function test_home_page_shows_the_contact_form(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('id="contact-form"', false)->assertSee('name="website"', false)->assertSee('Надіслати');
    }

    public function test_contact_form_lives_in_the_footer_of_every_page(): void
    {
        $this->withoutVite();
        Page::factory()->create(['slug' => 'about-test']);

        $this->assertNotContains(
            Module::where('name', 'like', '%Форма зв%')->value('id'),
            Page::where('slug', Page::ROOT_SLUG)->firstOrFail()->page_modules
        );
        $this->get('/about-test')->assertOk()->assertSee('id="contact-form"', false)->assertSee('Надіслати');
    }

    public function test_ajax_submission_creates_a_lead_and_returns_the_success_message(): void
    {
        $this->postJson('/contact', $this->payload())->assertOk()->assertJson(['message' => 'Дякуємо! Ми зв’яжемося з вами найближчим часом.']);

        $this->assertDatabaseHas('leads', ['name' => 'Іван', 'contact' => '+380501112233', 'source_url' => url('/'), 'is_processed' => false]);
    }

    public function test_new_lead_queues_notification_to_configured_address(): void
    {
        Mail::fake();
        config(['site.lead_notification_email' => 'sales@example.com']);

        $this->postJson('/contact', $this->payload())->assertOk();

        $lead = Lead::sole();
        Mail::assertQueued(LeadReceivedMail::class, fn (LeadReceivedMail $mail): bool => $mail->lead->id === $lead->id
            && $mail->hasTo('sales@example.com'));
    }

    public function test_plain_submission_redirects_back_and_shows_a_toast_without_an_anchor(): void
    {
        $this->withoutVite();

        $this->post('/contact', $this->payload())->assertRedirect(url('/'));

        $this->get('/')->assertSee('data-flash="Дякуємо! Ми зв’яжемося з вами найближчим часом."', false);
    }

    public function test_invalid_ajax_submission_returns_field_errors(): void
    {
        $this->postJson('/contact', $this->payload(['name' => '', 'contact' => 'ab']))->assertUnprocessable()->assertJsonValidationErrors(['name', 'contact']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_invalid_plain_submission_goes_back_to_the_page_with_errors(): void
    {
        $this->post('/contact', $this->payload(['name' => '']))->assertRedirect(url('/'))->assertSessionHasErrors('name');
    }

    public function test_bots_filling_the_honeypot_get_no_lead(): void
    {
        $this->post('/contact', $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect(url('/'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_foreign_source_url_is_not_stored_or_followed(): void
    {
        $this->post('/contact', $this->payload(['source' => 'https://evil.example/x']))->assertRedirect(url('/'));

        $this->assertNull(Lead::firstOrFail()->source_url);
    }

    public function test_source_with_site_prefix_and_foreign_host_is_not_followed(): void
    {
        $this->post('/contact', $this->payload(['source' => url('/').'@evil.example/phishing']))
            ->assertRedirect(url('/'));

        $this->assertNull(Lead::firstOrFail()->source_url);
    }

    public function test_form_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/contact', $this->payload())->assertRedirect();
        }

        $this->post('/contact', $this->payload())->assertStatus(429);
    }

    public function test_admin_sees_and_processes_leads(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->create(['name' => 'Марія']);

        $this->actingAs($admin, 'backpack')->get('/admin/lead')->assertOk()->assertSee('Заявки');
        $this->actingAs($admin, 'backpack')->postJson("/admin/lead/{$lead->id}/toggle")->assertOk()->assertJson(['value' => true]);
        $this->assertTrue($lead->fresh()->is_processed);
    }

    public function test_manager_without_permission_cannot_see_leads(): void
    {
        $user = User::factory()->withoutRole()->create();

        $this->actingAs($user, 'backpack')->get('/admin/lead')->assertRedirect('/admin/login');
    }

    public function test_footer_shows_configured_messengers_with_normalized_links(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update(['telegram' => '@nbu_desktop', 'whatsapp' => '+38 (050) 111-22-33', 'viber' => '+380501112233']);

        $this->get('/')->assertOk()
            ->assertSee('href="https://t.me/nbu_desktop"', false)
            ->assertSee('href="https://wa.me/380501112233"', false)
            ->assertSee('href="viber://chat?number=%2B380501112233"', false);
    }

    public function test_telegram_accepts_nick_or_link_and_empty_messengers_are_hidden(): void
    {
        $setting = SiteSetting::current();
        $setting->telegram = 'https://t.me/some_channel';
        $this->assertSame('https://t.me/some_channel', $setting->telegramUrl());

        $setting->telegram = 't.me/@nick_name';
        $this->assertSame('https://t.me/nick_name', $setting->telegramUrl());

        $setting->telegram = null;
        $this->assertSame([], $setting->messengers());
    }

    public function test_admin_validates_messenger_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $id = SiteSetting::current()->id;

        $this->actingAs($admin, 'backpack')->put("/admin/site-setting/{$id}", [
            'header_logo' => 'X', 'footer_text' => 'Y', 'telegram' => 'javascript:alert(1)', 'whatsapp' => 'abc', 'viber' => '',
        ])->assertSessionHasErrors(['telegram', 'whatsapp']);
    }

    public function test_order_button_falls_back_to_telegram_from_settings(): void
    {
        $this->withoutVite();
        config(['license.contact_url' => null]);
        SiteSetting::current()->update(['telegram' => '@nbu_desktop']);
        Package::factory()->create();

        $this->get('/')->assertSee('Замовити')->assertSee('https://t.me/nbu_desktop');
    }
}
