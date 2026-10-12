<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityLike;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Спільнота тимчасово вимкнена: розкоментуйте маршрути в routes/web.php і routes/backpack/custom.php.');
    }

    private function signIn(?License $license = null): License
    {
        $license ??= License::factory()->create(['customer' => 'Олена Коваль']);

        $this->post('/spilnota/kliuch', ['key' => $license->key])->assertSessionHasNoErrors();

        return $license;
    }

    /**
     * @return array<string, mixed>
     */
    private function topicPayload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'question', 'title' => 'Як налаштувати проксі?',
            'body' => 'Підкажіть, будь ласка, як підключити проксі до профілю AdsPower.',
        ];
    }

    public function test_index_lists_only_published_topics_and_hides_locked_body(): void
    {
        $this->withoutVite();
        CommunityTopic::factory()->create(['title' => 'Опублікована тема', 'body' => 'Секретний текст теми']);
        CommunityTopic::factory()->pending()->create(['title' => 'Тема на модерації']);

        $this->get('/spilnota')->assertOk()
            ->assertSee('Опублікована тема')
            ->assertDontSee('Тема на модерації')
            ->assertDontSee('Секретний текст теми')
            ->assertSee('Увійдіть за ключем ліцензії');
    }

    public function test_locked_topic_hides_body_and_comments_without_key(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create(['body' => 'Закритий зміст']);
        CommunityComment::factory()->create(['community_topic_id' => $topic->id, 'body' => 'Закритий коментар']);

        $this->get($topic->url())->assertOk()
            ->assertSee($topic->title)
            ->assertDontSee('Закритий зміст')
            ->assertDontSee('Закритий коментар')
            ->assertSee('noindex', false);
    }

    public function test_open_topic_is_readable_without_key(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->open()->create(['body' => 'Публічний зміст']);

        $this->get($topic->url())->assertOk()->assertSee('Публічний зміст');
    }

    public function test_valid_key_unlocks_topic_and_counts_a_view_once_per_session(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create(['body' => 'Закритий зміст']);
        $this->signIn();

        $this->get($topic->url())->assertOk()->assertSee('Закритий зміст');
        $this->get($topic->url())->assertOk();

        $this->assertSame(1, $topic->fresh()->views);
    }

    public function test_key_is_accepted_in_any_formatting(): void
    {
        $license = License::factory()->create();

        $this->post('/spilnota/kliuch', ['key' => strtolower(str_replace('-', ' ', $license->key))])
            ->assertSessionHas('community_license_id', $license->id);
    }

    public function test_unknown_expired_and_revoked_keys_are_rejected(): void
    {
        $expired = License::factory()->create(['expires_at' => now()->subDay()]);
        $revoked = License::factory()->create(['status' => License::STATUS_REVOKED]);

        foreach (['AAAA-BBBB-CCCC-DDDD', $expired->key, $revoked->key] as $key) {
            $this->post('/spilnota/kliuch', ['key' => $key])
                ->assertSessionHas('community_error')
                ->assertSessionMissing('community_license_id');
        }
    }

    public function test_session_ends_when_license_is_revoked(): void
    {
        $this->withoutVite();
        $license = $this->signIn();
        $license->update(['status' => License::STATUS_REVOKED]);

        $this->get('/spilnota')->assertOk()->assertSee('Увійдіть за ключем ліцензії');
    }

    public function test_leaving_clears_the_key(): void
    {
        $this->signIn();

        $this->post('/spilnota/vykhid')->assertRedirect('/spilnota');
        $this->assertNull(session('community_license_id'));
    }

    public function test_creating_a_topic_requires_a_key(): void
    {
        $this->get('/spilnota/nova')->assertRedirect('/spilnota');
        $this->post('/spilnota', $this->topicPayload())->assertRedirect('/spilnota');

        $this->assertDatabaseCount('community_topics', 0);
    }

    public function test_member_topic_waits_for_moderation(): void
    {
        $this->withoutVite();
        $license = $this->signIn();

        $this->get('/spilnota/nova')->assertOk();
        $this->post('/spilnota', $this->topicPayload())->assertRedirect('/spilnota')->assertSessionHas('community_status');

        $topic = CommunityTopic::firstOrFail();
        $this->assertSame(CommunityTopic::STATUS_PENDING, $topic->status);
        $this->assertSame($license->id, $topic->license_id);
        $this->assertSame('Олена', $topic->author_name);
        $this->get('/spilnota')->assertDontSee('Як налаштувати проксі?');
    }

    public function test_topic_validation_rules(): void
    {
        $this->signIn();

        $this->post('/spilnota', $this->topicPayload(['type' => 'news']))->assertSessionHasErrors('type');
        $this->post('/spilnota', $this->topicPayload(['title' => 'Ні']))->assertSessionHasErrors('title');
        $this->post('/spilnota', $this->topicPayload(['body' => 'Коротко']))->assertSessionHasErrors('body');
        $this->post('/spilnota', $this->topicPayload(['type' => 'review']))->assertSessionHasErrors('rating');
        $this->post('/spilnota', $this->topicPayload(['type' => 'review', 'rating' => 5]))->assertSessionHasNoErrors();

        $this->assertSame(5, CommunityTopic::firstOrFail()->rating);
    }

    public function test_honeypot_and_link_spam_are_silently_dropped(): void
    {
        $this->signIn();

        $this->post('/spilnota', $this->topicPayload(['website' => 'http://spam.test']))->assertSessionHas('community_status');
        $this->post('/spilnota', $this->topicPayload(['body' => 'Дивіться http://a.test http://b.test http://c.test']))->assertSessionHas('community_status');

        $this->assertDatabaseCount('community_topics', 0);
    }

    public function test_duplicate_topic_from_the_same_license_is_dropped(): void
    {
        $this->signIn();

        $this->post('/spilnota', $this->topicPayload());
        $this->post('/spilnota', $this->topicPayload(['title' => 'Інший заголовок']));

        $this->assertDatabaseCount('community_topics', 1);
    }

    public function test_posting_is_rate_limited(): void
    {
        $this->signIn();

        for ($i = 1; $i <= 10; $i++) {
            $this->post('/spilnota', $this->topicPayload(['title' => "Питання номер {$i}", 'body' => "Унікальний текст питання номер {$i}, достатньо довгий."]))->assertRedirect('/spilnota');
        }

        $this->post('/spilnota', $this->topicPayload())->assertStatus(429);
    }

    public function test_key_form_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/spilnota/kliuch', ['key' => 'AAAA-BBBB-CCCC-DDDD']);
        }

        $this->post('/spilnota/kliuch', ['key' => 'AAAA-BBBB-CCCC-DDDD'])->assertStatus(429);
    }

    public function test_comment_needs_key_and_waits_for_moderation(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create();

        $this->post("/spilnota/{$topic->slug}/komentari", ['body' => 'Корисна тема'])->assertSessionHas('community_error');
        $this->assertDatabaseCount('community_comments', 0);

        $this->signIn();
        $this->post("/spilnota/{$topic->slug}/komentari", ['body' => 'Корисна тема'])->assertSessionHas('community_status');

        $comment = CommunityComment::firstOrFail();
        $this->assertSame(CommunityComment::STATUS_PENDING, $comment->status);
        $this->get($topic->url())->assertDontSee('Корисна тема');
        $this->assertSame(0, $topic->fresh()->comments_count);
    }

    public function test_publishing_a_comment_updates_the_counter(): void
    {
        $topic = CommunityTopic::factory()->create();
        $comment = CommunityComment::factory()->pending()->create(['community_topic_id' => $topic->id]);

        $comment->update(['status' => CommunityComment::STATUS_PUBLISHED]);
        $this->assertSame(1, $topic->fresh()->comments_count);

        $comment->delete();
        $this->assertSame(0, $topic->fresh()->comments_count);
    }

    public function test_filter_search_and_sort(): void
    {
        $this->withoutVite();
        CommunityTopic::factory()->create(['type' => 'bug', 'title' => 'Збій запуску', 'views' => 5]);
        CommunityTopic::factory()->create(['type' => 'idea', 'title' => 'Ідея про звіти', 'views' => 50]);

        $this->get('/spilnota?type=bug')->assertSee('Збій запуску')->assertDontSee('Ідея про звіти');
        $this->get('/spilnota?q=звіти')->assertSee('Ідея про звіти')->assertDontSee('Збій запуску');
        $this->get('/spilnota?sort=popular')->assertSeeInOrder(['Ідея про звіти', 'Збій запуску']);
    }

    public function test_pinned_topics_come_first(): void
    {
        $this->withoutVite();
        CommunityTopic::factory()->create(['title' => 'Звичайна тема', 'published_at' => now()]);
        CommunityTopic::factory()->staff()->create(['title' => 'Закріплена новина', 'type' => 'news', 'is_pinned' => true, 'published_at' => now()->subWeek()]);

        $this->get('/spilnota')->assertSeeInOrder(['Закріплена новина', 'Звичайна тема']);
    }

    public function test_topic_slug_cannot_take_a_service_route(): void
    {
        $topic = CommunityTopic::factory()->create(['title' => 'Нова']);

        $this->assertSame('nova-tema', $topic->slug);
    }

    public function test_unpublished_topic_is_not_found(): void
    {
        $topic = CommunityTopic::factory()->pending()->create();

        $this->get($topic->url())->assertNotFound();
    }

    public function test_community_is_linked_in_the_header(): void
    {
        $this->withoutVite();

        $this->get('/')->assertSee('href="/spilnota"', false);
    }

    public function test_locked_topic_shows_key_button_and_hidden_comments_count(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create(['comments_count' => 3]);

        $this->get($topic->url())->assertOk()
            ->assertSee('Контент для власників ліцензії')
            ->assertSee('Ввести ключ')
            ->assertSee('Приховано коментарів: 3');
    }

    public function test_like_requires_a_key_and_toggles_once_per_license(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create();

        $this->post("/spilnota/{$topic->slug}/like")->assertSessionHas('community_error');
        $this->assertSame(0, $topic->fresh()->likes_count);

        $this->signIn();
        $this->post("/spilnota/{$topic->slug}/like");
        $this->post("/spilnota/{$topic->slug}/like");
        $this->assertSame(0, $topic->fresh()->likes_count);

        $this->post("/spilnota/{$topic->slug}/like");
        $this->assertSame(1, $topic->fresh()->likes_count);
        $this->assertDatabaseCount('community_likes', 1);
        $this->get($topic->url())->assertSee('aria-pressed="true"', false);
    }

    public function test_like_count_is_visible_to_everyone(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create();
        CommunityLike::factory()->create(['community_topic_id' => $topic->id]);

        $this->get($topic->url())->assertOk()->assertSee('title="Подобається"', false);
        $this->assertSame(1, $topic->fresh()->likes_count);
    }

    public function test_report_requires_a_key_and_is_saved_once_per_license(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->create();

        $this->post("/spilnota/{$topic->slug}/skarha", ['reason' => 'Спам'])->assertSessionHas('community_error');
        $this->assertDatabaseCount('community_reports', 0);

        $license = $this->signIn();
        $this->post("/spilnota/{$topic->slug}/skarha", ['reason' => 'Спам'])->assertSessionHas('community_status');
        $this->post("/spilnota/{$topic->slug}/skarha", ['reason' => 'Ще раз'])->assertSessionHas('community_status');

        $this->assertDatabaseCount('community_reports', 1);
        $this->assertDatabaseHas('community_reports', ['community_topic_id' => $topic->id, 'license_id' => $license->id, 'reason' => 'Спам']);
    }

    public function test_share_and_report_buttons_are_shown_on_a_topic(): void
    {
        $this->withoutVite();
        $topic = CommunityTopic::factory()->open()->create();

        $this->get($topic->url())->assertSee('data-cm-share="'.$topic->url().'"', false)->assertDontSee('cm-report-dialog', false);

        $this->signIn();
        $this->get($topic->url())->assertSee('cm-report-dialog', false);
    }

    public function test_admin_sees_and_processes_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $report = CommunityReport::factory()->create();

        $this->actingAs($admin, 'backpack')->get('/admin/community-report')->assertOk();
        $this->actingAs($admin, 'backpack')->postJson("/admin/community-report/{$report->id}/toggle")->assertJson(['value' => true]);
    }

    public function test_admin_moderates_topics_and_comments(): void
    {
        $admin = User::factory()->admin()->create();
        $topic = CommunityTopic::factory()->pending()->create();
        $comment = CommunityComment::factory()->pending()->create(['community_topic_id' => $topic->id]);

        $this->actingAs($admin, 'backpack')->get('/admin/community-topic')->assertOk();
        $this->actingAs($admin, 'backpack')->get('/admin/community-comment')->assertOk();
        $this->actingAs($admin, 'backpack')->get('/admin/community-topic/create')->assertOk();

        $this->actingAs($admin, 'backpack')->postJson("/admin/community-topic/{$topic->id}/toggle")->assertJson(['value' => true]);
        $this->actingAs($admin, 'backpack')->postJson("/admin/community-comment/{$comment->id}/toggle")->assertJson(['value' => true]);

        $this->assertSame(CommunityTopic::STATUS_PUBLISHED, $topic->fresh()->status);
        $this->assertNotNull($topic->fresh()->published_at);
        $this->assertSame(1, $topic->fresh()->comments_count);
    }

    public function test_admin_publishes_a_staff_news_topic(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/community-topic', [
            'type' => 'news', 'title' => 'Вийшла нова версія', 'body' => 'Що змінилось у версії.',
            'author_name' => 'Команда Numis', 'status' => 'published', 'is_staff' => 1, 'is_members_only' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('community_topics', ['title' => 'Вийшла нова версія', 'is_staff' => true, 'is_members_only' => false]);
    }

    public function test_user_without_permission_cannot_open_community_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'backpack')->get('/admin/community-topic')->assertForbidden();
    }
}
