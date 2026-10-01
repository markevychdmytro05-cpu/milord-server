<?php

namespace App\Providers;

use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\LicensePayment;
use App\Support\ResponseSigner;
use App\View\Composers\SiteLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
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
        RateLimiter::for('license-api', fn (Request $request) => Limit::perMinute(config('license.rate_limit'))->by($request->ip()));

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

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
