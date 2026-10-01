<?php

namespace Tests\Feature;

use App\Models\LanguageLine;
use App\Models\User;
use App\Services\TranslationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_translation_key_used_in_code_exists_in_the_ukrainian_lang_files(): void
    {
        $missing = [];
        $finder = (new Finder)->files()->in([app_path(), resource_path('views')])->name('*.php');

        foreach ($finder as $file) {
            if (str_contains($file->getPathname(), 'resources/views/vendor/backpack') || str_contains($file->getPathname(), 'resources/views/admin')) {
                continue;
            }

            preg_match_all('/(?:__|trans|@lang)\(\s*[\'"]([a-z_]+\.[A-Za-z0-9_.]+)[\'"]/', $file->getContents(), $matches);

            foreach ($matches[1] as $key) {
                if (! str_ends_with($key, '.') && ! Lang::has($key, 'uk', false)) {
                    $missing[$key] = $file->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $missing, 'Ключі без перекладу в lang/uk: '.json_encode($missing, JSON_UNESCAPED_UNICODE));
    }

    /** Перекладач кешує завантажені групи в межах запиту, тож у тестах скидаємо кеш після змін у базі. */
    private function reloadTranslations(): void
    {
        app('translator')->setLoaded([]);
    }

    public function test_database_line_overrides_the_value_from_the_lang_file(): void
    {
        $this->assertSame('Детальніше', __('site.more'));

        LanguageLine::create(['group' => 'site', 'key' => 'more', 'text' => ['uk' => 'Дізнатися більше']]);
        $this->reloadTranslations();

        $this->assertSame('Дізнатися більше', __('site.more'));
    }

    public function test_nested_keys_are_overridden_and_placeholders_work(): void
    {
        LanguageLine::create(['group' => 'coins', 'key' => 'card.pcs', 'text' => ['uk' => ':count штук']]);
        $this->reloadTranslations();

        $this->assertSame('5 штук', __('coins.card.pcs', ['count' => 5]));
        $this->assertSame('Дата уточнюється', __('coins.card.date_pending'));
    }

    public function test_import_adds_keys_from_files_and_keeps_existing_admin_edits(): void
    {
        LanguageLine::create(['group' => 'site', 'key' => 'more', 'text' => ['uk' => 'Моя правка']]);

        $first = app(TranslationSyncService::class)->importFromFiles();
        $second = app(TranslationSyncService::class)->importFromFiles();

        $this->assertGreaterThan(50, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame('Моя правка', LanguageLine::where('group', 'site')->where('key', 'more')->first()->text['uk']);
        $this->assertNotNull(LanguageLine::where('group', 'coins')->where('key', 'months.9')->first());
    }

    public function test_export_writes_database_values_into_lang_files_and_keeps_other_keys(): void
    {
        $dir = sys_get_temp_dir().'/lang-'.uniqid();
        File::ensureDirectoryExists($dir.'/uk');
        File::put($dir.'/uk/demo.php', "<?php\n\nreturn ['keep' => 'Залишається', 'nested' => ['a' => 'Один']];\n");
        $this->app->useLangPath($dir);

        LanguageLine::create(['group' => 'demo', 'key' => 'nested.a', 'text' => ['uk' => "Правка з 'лапками'"]]);
        LanguageLine::create(['group' => 'demo', 'key' => 'new_key', 'text' => ['uk' => 'Новий']]);

        $this->assertSame(1, app(TranslationSyncService::class)->exportToFiles());

        $data = require $dir.'/uk/demo.php';
        $this->assertSame('Залишається', $data['keep']);
        $this->assertSame("Правка з 'лапками'", $data['nested']['a']);
        $this->assertSame('Новий', $data['new_key']);

        File::deleteDirectory($dir);
    }

    public function test_admin_lists_creates_edits_and_deletes_translations(): void
    {
        $admin = User::factory()->admin()->create();
        $line = LanguageLine::create(['group' => 'site', 'key' => 'more', 'text' => ['uk' => 'Детальніше']]);

        $this->actingAs($admin, 'backpack')->get('/admin/translation')->assertOk();
        $this->actingAs($admin, 'backpack')->postJson('/admin/translation/search')->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->actingAs($admin, 'backpack')->get('/admin/translation/create')->assertOk()->assertSee('text[uk]', false);
        $this->actingAs($admin, 'backpack')->get("/admin/translation/{$line->id}/edit")->assertOk()->assertSee('Детальніше');

        $this->actingAs($admin, 'backpack')->post('/admin/translation', ['group' => 'site', 'key' => 'custom', 'text' => ['uk' => 'Мій текст']])->assertRedirect();
        $this->reloadTranslations();
        $this->assertSame('Мій текст', __('site.custom'));

        $this->actingAs($admin, 'backpack')->put("/admin/translation/{$line->id}", ['id' => $line->id, 'group' => 'site', 'key' => 'more', 'text' => ['uk' => 'Ще']])->assertRedirect();
        $this->reloadTranslations();
        $this->assertSame('Ще', __('site.more'));

        $this->actingAs($admin, 'backpack')->delete("/admin/translation/{$line->id}")->assertOk();
        $this->reloadTranslations();
        $this->assertSame('Детальніше', __('site.more'));
    }

    public function test_admin_validates_translation_form(): void
    {
        $admin = User::factory()->admin()->create();
        LanguageLine::create(['group' => 'site', 'key' => 'more', 'text' => ['uk' => 'Детальніше']]);

        $this->actingAs($admin, 'backpack')->post('/admin/translation', ['group' => 'site', 'key' => 'more', 'text' => ['uk' => 'Дубль']])->assertSessionHasErrors('key');
        $this->actingAs($admin, 'backpack')->post('/admin/translation', ['group' => 'bad group!', 'key' => 'x', 'text' => ['uk' => 'a']])->assertSessionHasErrors('group');
        $this->actingAs($admin, 'backpack')->post('/admin/translation', ['group' => 'site', 'key' => 'empty', 'text' => ['uk' => '']])->assertSessionHasErrors('text.uk');
    }

    public function test_admin_can_sync_and_export_from_buttons_and_users_without_permission_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/translation/sync')->assertRedirect();
        $this->assertGreaterThan(50, LanguageLine::count());

        $this->actingAs($manager, 'backpack')->get('/admin/translation')->assertForbidden();
        $this->actingAs($manager, 'backpack')->post('/admin/translation/sync')->assertForbidden();
    }

    public function test_translation_commands_run(): void
    {
        $this->artisan('translations:sync')->assertSuccessful();
        $this->assertGreaterThan(50, LanguageLine::count());
    }
}
