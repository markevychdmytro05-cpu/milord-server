<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AdminPermissions;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckIfAdmin
{
    private function checkIfUserIsAdmin($user)
    {
        return $user instanceof User && $user->is_active && $user->roles->isNotEmpty();
    }

    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            return redirect()->guest(backpack_url('login'));
        }
    }

    public function handle($request, Closure $next)
    {
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (! $this->checkIfUserIsAdmin(backpack_user())) {
            backpack_auth()->logout();

            return $this->respondToUnauthorizedRequest($request);
        }

        if ($request->is(trim(config('backpack.base.route_prefix'), '/').'/dashboard') && ! backpack_user()->can('dashboard_view')) {
            $path = AdminPermissions::landingPath(backpack_user());
            abort_if($path === null, 403, 'Для цього користувача ще не налаштовано доступ до розділів.');

            return redirect(backpack_url($path));
        }

        return $next($request);
    }
}
