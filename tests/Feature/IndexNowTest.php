<?php

namespace Tests\Feature;

use App\Jobs\SubmitToIndexNow;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_file_is_served_only_for_the_configured_key(): void
    {
        config(['services.indexnow.key' => 'abcdef1234567890']);

        $this->get('/abcdef1234567890.txt')->assertOk()->assertSee('abcdef1234567890', false);
        $this->get('/wrongkey1234567890.txt')->assertNotFound();
    }

    public function test_key_file_is_not_served_without_a_configured_key(): void
    {
        config(['services.indexnow.key' => null]);

        $this->get('/abcdef1234567890.txt')->assertNotFound();
    }

    public function test_job_posts_urls_to_indexnow(): void
    {
        config(['services.indexnow.key' => 'abcdef1234567890', 'app.url' => 'https://numis.in.ua']);
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

        (new SubmitToIndexNow(['https://numis.in.ua/blog']))->handle();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.indexnow.org/IndexNow'
            && $request['host'] === 'numis.in.ua'
            && $request['keyLocation'] === 'https://numis.in.ua/abcdef1234567890.txt'
            && $request['urlList'] === ['https://numis.in.ua/blog']);
    }

    public function test_saving_a_published_page_queues_a_submission_only_with_a_key(): void
    {
        Queue::fake();
        config(['services.indexnow.key' => null]);
        Page::factory()->create(['is_published' => true]);
        Queue::assertNotPushed(SubmitToIndexNow::class);

        config(['services.indexnow.key' => 'abcdef1234567890']);
        Page::factory()->create(['is_published' => true]);
        Queue::assertPushed(SubmitToIndexNow::class);
    }
}
