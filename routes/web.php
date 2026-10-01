<?php

use App\Http\Controllers\CoinController;
use App\Http\Controllers\ContactController;
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
Route::get('monety-nbu', [CoinController::class, 'index'])->name('coins.index');
Route::get('monety-nbu/{slug}', [CoinController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('coins.show');
Route::get('services/{slug}', [PageController::class, 'service'])->where('slug', '[A-Za-z0-9-]+')->name('services.show');
Route::get('{slug}', [PageController::class, 'page'])->where('slug', '[A-Za-z0-9-]+')->name('pages.show');
