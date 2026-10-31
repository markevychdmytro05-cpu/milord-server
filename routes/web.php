<?php

use App\Http\Controllers\CoinController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndexNowKeyController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\RequireSameOrigin;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('contact', [ContactController::class, 'store'])->middleware([RequireSameOrigin::class, 'throttle:contact'])->name('contact.store');
Route::get('robots.txt', function () {
    $aiCrawlers = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'CCBot'];
    $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /api', 'Disallow: /cdn-cgi/', 'Disallow: /zavantazhyty/', ''];
    foreach ($aiCrawlers as $crawler) {
        array_push($lines, 'User-agent: '.$crawler, 'Allow: /', 'Disallow: /admin', 'Disallow: /api', 'Disallow: /cdn-cgi/', 'Disallow: /zavantazhyty/', '');
    }
    $lines[] = 'Sitemap: '.route('sitemap');

    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('feed.xml', FeedController::class)->name('feed');
Route::get('llms.txt', [LlmsController::class, 'index'])->name('llms');
Route::get('{key}.txt', IndexNowKeyController::class)->where('key', '[A-Za-z0-9]{8,128}');
Route::get('llms-full.txt', [LlmsController::class, 'full'])->name('llms.full');
Route::get('zavantazhyty', [DownloadController::class, 'index'])->name('downloads.index');
Route::get('zavantazhyty/{target}', [DownloadController::class, 'start'])->where('target', '(win|mac|linux)-(x64|arm64)')->middleware('throttle:downloads')->name('downloads.start');
Route::get('zavantazhyty/{release}/file', [DownloadController::class, 'file'])->whereNumber('release')->middleware('signed')->name('downloads.file');
Route::get('monety-nbu', [CoinController::class, 'index'])->name('coins.index');
Route::get('monety-nbu/{slug}', [CoinController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('coins.show');
Route::get('services/{slug}', [PageController::class, 'service'])->where('slug', '[A-Za-z0-9-]+')->name('services.show');
Route::permanentRedirect('baza-znan-numis', '/blog');
Route::permanentRedirect('kalendar-vypusku-monet-nbu-2026', '/blog/plan-vypusku-monet-nbu-na-2026-rik-shcho-vyyde-do-kintsia-roku');
Route::get('baza-znan/{slug?}', fn (Request $request, ?string $slug = null) => redirect()->to(
    ($slug ? route('articles.show', $slug) : route('pages.show', Page::BLOG_SLUG)).($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301,
))->where('slug', '[A-Za-z0-9-]+');
Route::get('blog/{slug}', [PageController::class, 'article'])->where('slug', '[A-Za-z0-9-]+')->name('articles.show');
Route::get('{slug}', [PageController::class, 'page'])->where('slug', '[A-Za-z0-9-]+')->name('pages.show');
