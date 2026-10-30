<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityCommentRequest;
use App\Http\Requests\CommunityKeyRequest;
use App\Http\Requests\CommunityTopicRequest;
use App\Models\CommunityComment;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use App\Models\License;
use App\Support\CommunityAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), CommunityTopic::TYPES, true) ? $request->query('type') : null;
        $sort = in_array($request->query('sort'), ['popular', 'discussed'], true) ? $request->query('sort') : 'new';
        $search = trim((string) $request->query('q'));

        $topics = CommunityTopic::published()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes(mb_substr($search, 0, 100), '%_\\').'%'))
            ->when($sort === 'new', fn ($query) => $query->orderByDesc('is_pinned')->orderByDesc('published_at'))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('views'))
            ->when($sort === 'discussed', fn ($query) => $query->orderByDesc('comments_count'))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('community.index', [
            'topics' => $topics,
            'type' => $type,
            'sort' => $sort,
            'search' => $search,
            'counts' => CommunityTopic::published()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'license' => CommunityAccess::license($request),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $topic = CommunityTopic::published()->where('slug', $slug)->firstOrFail();
        $license = CommunityAccess::license($request);
        $locked = $topic->is_members_only && ! $license;

        if (! $locked && ! $request->session()->has('community_seen.'.$topic->id)) {
            $request->session()->put('community_seen.'.$topic->id, true);
            $topic->increment('views');
        }

        return view('community.show', [
            'topic' => $topic,
            'license' => $license,
            'locked' => $locked,
            'liked' => $license ? $topic->likes()->where('license_id', $license->id)->exists() : false,
            'comments' => $locked ? collect() : $topic->publishedComments()->get(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $license = CommunityAccess::license($request);

        if (! $license) {
            return redirect()->route('community.index')->with('community_error', __('community.key.needed'));
        }

        return view('community.create', ['license' => $license, 'type' => $request->query('type')]);
    }

    public function store(CommunityTopicRequest $request): RedirectResponse
    {
        $license = CommunityAccess::license($request);

        if (! $license) {
            return redirect()->route('community.index')->with('community_error', __('community.key.needed'));
        }

        if ($request->filled('website') || $this->looksLikeSpam($request->string('title').' '.$request->string('body'), $license, topic: true)) {
            return redirect()->route('community.index')->with('community_status', __('community.topic.sent'));
        }

        CommunityTopic::create([
            ...$request->safe()->only(['type', 'title', 'body']),
            'rating' => $request->input('type') === 'review' ? $request->integer('rating') : null,
            'author_name' => $this->authorName($request->input('name'), $license),
            'license_id' => $license->id,
            'status' => CommunityTopic::STATUS_PENDING,
        ]);

        return redirect()->route('community.index')->with('community_status', __('community.topic.sent'));
    }

    public function comment(CommunityCommentRequest $request, string $slug): RedirectResponse
    {
        $topic = CommunityTopic::published()->where('slug', $slug)->firstOrFail();
        $license = CommunityAccess::license($request);

        if (! $license) {
            return redirect()->route('community.show', $topic->slug)->with('community_error', __('community.key.needed'));
        }

        if (! $request->filled('website') && ! $this->looksLikeSpam($request->string('body'), $license, topic: false)) {
            $topic->comments()->create([
                'body' => $request->string('body'),
                'author_name' => $this->authorName($request->input('name'), $license),
                'license_id' => $license->id,
                'status' => CommunityComment::STATUS_PENDING,
            ]);
        }

        return redirect()->route('community.show', $topic->slug)->with('community_status', __('community.comment.sent'));
    }

    public function like(Request $request, string $slug): RedirectResponse
    {
        $topic = CommunityTopic::published()->where('slug', $slug)->firstOrFail();
        $license = CommunityAccess::license($request);

        if (! $license) {
            return redirect()->route('community.show', $topic->slug)->with('community_error', __('community.key.needed'));
        }

        $like = $topic->likes()->where('license_id', $license->id)->first();
        $like ? $like->delete() : $topic->likes()->create(['license_id' => $license->id]);

        return redirect()->route('community.show', $topic->slug);
    }

    public function report(Request $request, string $slug): RedirectResponse
    {
        $topic = CommunityTopic::published()->where('slug', $slug)->firstOrFail();
        $license = CommunityAccess::license($request);

        if (! $license) {
            return redirect()->route('community.show', $topic->slug)->with('community_error', __('community.key.needed'));
        }

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        CommunityReport::firstOrCreate(
            ['community_topic_id' => $topic->id, 'license_id' => $license->id],
            ['reason' => $data['reason'] ?? null],
        );

        return redirect()->route('community.show', $topic->slug)->with('community_status', __('community.report.sent'));
    }

    public function enter(CommunityKeyRequest $request): RedirectResponse
    {
        if (! CommunityAccess::attempt($request, $request->string('key'))) {
            return back()->withInput($request->only('key'))->with('community_error', __('community.key.invalid'));
        }

        return back()->with('community_status', __('community.key.accepted'));
    }

    public function leave(Request $request): RedirectResponse
    {
        CommunityAccess::leave($request);

        return redirect()->route('community.index');
    }

    private function authorName(?string $name, License $license): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $name : (string) (explode(' ', trim($license->customer))[0] ?: __('community.anonymous'));
    }

    private function looksLikeSpam(string $text, License $license, bool $topic): bool
    {
        if (preg_match_all('~https?://|www\.|t\.me/~iu', $text) > 2) {
            return true;
        }

        $query = $topic ? CommunityTopic::query()->where('body', 'like', '%'.mb_substr(trim($text), -40).'%') : CommunityComment::query()->where('body', trim($text));

        return $query->where('license_id', $license->id)->where('created_at', '>=', now()->subDay())->exists();
    }
}
