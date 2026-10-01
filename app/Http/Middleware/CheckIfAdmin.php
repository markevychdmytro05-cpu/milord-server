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
    /**
     * Checked that the logged in user is an administrator.
     *
     * --------------
     * VERY IMPORTANT
     * --------------
     * If you have both regular users and admins inside the same table, change
     * the contents of this method to check that the logged in user
     * is an admin, and not a regular user.
     *
     * Additionally, in Laravel 7+, you should change app/Providers/RouteServiceProvider::HOME
     * which defines the route where a logged in user (but not admin) gets redirected
     * when trying to access an admin route. By default it's '/home' but Backpack
     * does not have a '/home' route, use something you've built for your users
     * (again - users, not admins).
     *
     * @param  ?Authenticatable  $user
     * @return bool
     */
    private function checkIfUserIsAdmin($user)
    {
        return $user instanceof User && $user->is_active && $user->roles->isNotEmpty();
    }

    /**
     * Answer to unauthorized access request.
     *
     * @param  Request  $request
     * @return Response|RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            return redirect()->guest(backpack_url('login'));
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
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
