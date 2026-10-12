<?php

namespace Tests\Feature;

use App\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function releaseWithFile(array $attributes = []): Release
    {
        Storage::fake(Release::DISK);
        $release = Release::factory()->create($attributes);
        Storage::disk(Release::DISK)->put($release->file_path, 'installer');

        return $release;
    }

    public function test_downloads_page_lists_latest_active_release_per_platform(): void
    {
        Release::factory()->create(['version' => '1.0.0', 'platform' => 'win']);
        Release::factory()->create(['version' => '1.1.0', 'platform' => 'win']);
        Release::factory()->inactive()->create(['version' => '9.0.0', 'platform' => 'win']);
        Release::factory()->platform('mac', 'arm64')->create(['version' => '1.5.0']);

        $this->get(route('downloads.index'))->assertOk()
            ->assertSee('Версія 1.1.0 від', false)->assertSee('Версія 1.5.0 від', false)
            ->assertDontSee('Версія 1.0.0 від', false)->assertDontSee('9.0.0')->assertSee('macOS ARM64');
    }

    public function test_download_link_redirects_to_a_signed_file_url_without_a_key(): void
    {
        $this->releaseWithFile();

        $response = $this->get(route('downloads.start', 'win-x64'));

        $response->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk()->assertDownload();
        $this->assertSame(1, Release::sole()->downloads_count);
    }

    public function test_unavailable_target_is_not_found(): void
    {
        $this->releaseWithFile();

        $this->get(route('downloads.start', 'linux-x64'))->assertNotFound();
    }

    public function test_file_route_requires_a_valid_signature(): void
    {
        $release = $this->releaseWithFile();

        $this->get(route('downloads.file', $release))->assertForbidden();
        $this->get($release->temporaryUrl(5).'x')->assertForbidden();
    }

    public function test_deactivated_release_cannot_be_downloaded_even_with_a_valid_link(): void
    {
        $release = $this->releaseWithFile();
        $url = $release->temporaryUrl(5);
        $release->update(['is_active' => false]);

        $this->get($url)->assertNotFound();
    }

    public function test_publish_command_registers_release_from_file_name(): void
    {
        Storage::fake(Release::DISK);
        $path = sys_get_temp_dir().'/nbu-desktop-1.4.0-mac-arm64.dmg';
        file_put_contents($path, 'dmg-bytes');

        $this->artisan('release:publish', ['file' => $path, '--notes' => 'Нове', '--activate' => true])->assertSuccessful();

        $release = Release::sole();
        $this->assertSame(['1.4.0', 'mac', 'arm64', true], [$release->version, $release->platform, $release->arch, $release->is_active]);
        $this->assertSame(hash('sha256', 'dmg-bytes'), $release->sha256);
        Storage::disk(Release::DISK)->assertExists($release->file_path);

        $this->artisan('release:publish', ['file' => $path])->assertFailed();
        unlink($path);
    }

    public function test_publish_command_rejects_badly_named_files(): void
    {
        $path = sys_get_temp_dir().'/installer.exe';
        file_put_contents($path, 'x');

        $this->artisan('release:publish', ['file' => $path])->assertFailed();
        unlink($path);
    }
}
