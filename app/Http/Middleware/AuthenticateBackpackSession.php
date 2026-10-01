<?php

namespace App\Http\Middleware;

use Backpack\CRUD\app\Http\Middleware\AuthenticateSession;
use Closure;
use Illuminate\Http\Request;

class AuthenticateBackpackSession extends AuthenticateSession
{
    public function handle($request, Closure $next)
    {
        if (! $request->hasSession() || ! $this->user) {
            return $next($request);
        }

        $sessionKey = 'password_hash_'.backpack_guard_name();

        if (backpack_auth()->viaRemember()) {
            $cookieHash = explode('|', (string) $request->cookies->get(backpack_auth()->getRecallerName()))[2] ?? null;

            if (! $cookieHash || ! $this->validatePasswordHash($this->user->getAuthPassword(), $cookieHash)) {
                $this->logout($request);
            }
        }

        if (! $request->session()->has($sessionKey)) {
            $this->storePasswordHashInSession($request);
        }

        if (! $this->validatePasswordHash($this->user->getAuthPassword(), (string) $request->session()->get($sessionKey))) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            if (backpack_auth()->user()) {
                $this->storePasswordHashInSession($request);
            }
        });
    }

    /**
     * @param  Request  $request
     */
    protected function storePasswordHashInSession($request): void
    {
        if (! $this->user) {
            return;
        }

        $request->session()->put(
            'password_hash_'.backpack_guard_name(),
            backpack_auth()->hashPasswordForCookie($this->user->getAuthPassword()),
        );
    }

    protected function guard()
    {
        return backpack_auth();
    }
}
