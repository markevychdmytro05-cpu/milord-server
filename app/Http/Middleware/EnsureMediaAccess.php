<?php

namespace App\Http\Middleware;

use App\Support\MediaAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMediaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(MediaAccess::allowed(), 403);

        return $next($request);
    }
}
