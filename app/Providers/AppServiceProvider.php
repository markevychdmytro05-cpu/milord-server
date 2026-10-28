<?php

namespace App\Providers;

use App\Jobs\SubmitToIndexNow;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\LicensePayment;
use App\Models\Page;
use App\Support\ResponseSigner;
use App\View\Composers\SiteLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /** Публічні діапазони Cloudflare (https://www.cloudflare.com/ips/): оновлюйте, якщо Cloudflare їх змінить. */
    private const CLOUDFLARE_PROXIES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
        '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Канонічні адреси, sitemap і JSON-LD будуються з запиту; у продакшні вони завжди мають вказувати на APP_URL (https).
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Нова чи змінена опублікована сторінка одразу йде в IndexNow (лише коли задано INDEXNOW_KEY і сторінка індексується).
        Page::saved(function (Page $page): void {
            if (config('services.indexnow.key') && $page->is_published && ($page->robots?->isIndexable() ?? true)) {
                SubmitToIndexNow::dispatch([$page->url()])->afterCommit();
            }
        });

        // Довірені проксі читаємо з конфігу (а не з env() у bootstrap/app.php): так це працює і з config:cache.
        if ($proxies = config('site.trusted_proxies')) {
            TrustProxies::at($proxies === 'cloudflare' ? self::CLOUDFLARE_PROXIES : array_map('trim', explode(',', $proxies)));
        }

        RateLimiter::for('license-api', fn (Request $request) => Limit::perMinute(config('license.rate_limit'))->by($request->ip()));

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('downloads', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('community-key', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('community-post', fn (Request $request) => Limit::perHour(10)->by($request->session()->get('community_license_id') ?: $request->ip()));

        Event::listen(DiagnosingHealth::class, function (): void {
            DB::connection()->getPdo();

            if (! Cache::put('health:cache', 'ready', 60) || Cache::get('health:cache') !== 'ready') {
                throw new RuntimeException('Application cache is unavailable.');
            }

            if (app()->isProduction()) {
                ResponseSigner::sign('health');
            }
        });

        View::composer('layouts.public', SiteLayoutComposer::class);

        View::composer(array_unique([
            'backpack.ui::dashboard',
            config('backpack.ui.view_namespace').'dashboard',
            config('backpack.ui.view_namespace_fallback').'dashboard',
        ]), function (\Illuminate\View\View $view): void {
            Gate::forUser(backpack_user())->authorize('dashboard_view');
            $valid = License::where('status', License::STATUS_ACTIVE)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));

            $view->with([
                'activeLicenseCount' => backpack_user()->can('licenses_view') ? (clone $valid)->count() : null,
                'expiringSoon' => backpack_user()->can('licenses_view') ? (clone $valid)->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(7))
                    ->orderBy('expires_at')->orderBy('id')->paginate(20) : null,
                'onlineDeviceCount' => backpack_user()->can('devices_view') ? LicenseActivation::where('last_seen_at', '>=', now()->subDay())->count() : null,
                'receivedThisMonthCents' => backpack_user()->can('payments_view') ? (int) LicensePayment::whereNull('voided_at')
                    ->where('currency', 'USD')
                    ->where('paid_at', '>=', now()->startOfMonth())
                    ->where('paid_at', '<', now()->startOfMonth()->addMonth())
                    ->sum('amount_cents') : null,
            ]);
        });
    }
}
