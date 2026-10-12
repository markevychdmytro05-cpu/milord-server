<?php

use App\Http\Controllers\CoinController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::get('robots.txt', function () {
    $aiCrawlers = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'CCBot'];
    $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /api', ''];
    foreach ($aiCrawlers as $crawler) {
        array_push($lines, 'User-agent: '.$crawler, 'Allow: /', 'Disallow: /admin', 'Disallow: /api', '');
    }
    $lines[] = 'Sitemap: '.route('sitemap');

    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('llms.txt', [LlmsController::class, 'index'])->name('llms');
Route::get('llms-full.txt', [LlmsController::class, 'full'])->name('llms.full');
// Спільнота тимчасово вимкнена: щоб увімкнути, розкоментуйте маршрути нижче й пункт «Спільнота» в шапці (адмінка: Меню шапки).
/*
Route::prefix('spilnota')->name('community.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CommunityController::class, 'index'])->name('index');
    Route::get('nova', [\App\Http\Controllers\CommunityController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\CommunityController::class, 'store'])->middleware('throttle:community-post')->name('store');
    Route::post('kliuch', [\App\Http\Controllers\CommunityController::class, 'enter'])->middleware('throttle:community-key')->name('enter');
    Route::post('vykhid', [\App\Http\Controllers\CommunityController::class, 'leave'])->name('leave');
    Route::get('{slug}', [\App\Http\Controllers\CommunityController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('show');
    Route::post('{slug}/like', [\App\Http\Controllers\CommunityController::class, 'like'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:60,1')->name('like');
    Route::post('{slug}/skarha', [\App\Http\Controllers\CommunityController::class, 'report'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:community-post')->name('report');
    Route::post('{slug}/komentari', [\App\Http\Controllers\CommunityController::class, 'comment'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:community-post')->name('comment');
});
*/
Route::get('zavantazhyty', [DownloadController::class, 'index'])->name('downloads.index');
Route::get('zavantazhyty/{target}', [DownloadController::class, 'start'])->where('target', '(win|mac|linux)-(x64|arm64)')->middleware('throttle:downloads')->name('downloads.start');
Route::get('zavantazhyty/{release}/file', [DownloadController::class, 'file'])->whereNumber('release')->middleware('signed')->name('downloads.file');
Route::get('monety-nbu', [CoinController::class, 'index'])->name('coins.index');
Route::get('monety-nbu/{slug}', [CoinController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('coins.show');
Route::get('services/{slug}', [PageController::class, 'service'])->where('slug', '[A-Za-z0-9-]+')->name('services.show');
Route::permanentRedirect('baza-znan-numis', '/baza-znan');
Route::get('baza-znan/{slug}', [PageController::class, 'article'])->where('slug', '[A-Za-z0-9-]+')->name('articles.show');
Route::get('{slug}', [PageController::class, 'page'])->where('slug', '[A-Za-z0-9-]+')->name('pages.show');
