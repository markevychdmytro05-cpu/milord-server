<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Канонічні адреси пагінації: перша сторінка без параметра page, неіснуюча – перенаправлення на останню наявну.
 */
final class PaginationRedirect
{
    /** ?page=1, ?page=0 і нечислові значення ведуть на адресу без параметра. */
    public static function fromFirstPage(Request $request): ?RedirectResponse
    {
        if (! $request->query->has('page') || $request->integer('page') > 1) {
            return null;
        }

        return self::to($request, null);
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     */
    public static function fromOutOfRange(Request $request, LengthAwarePaginator $paginator): ?RedirectResponse
    {
        if ($paginator->currentPage() <= $paginator->lastPage()) {
            return null;
        }

        return self::to($request, $paginator->lastPage());
    }

    private static function to(Request $request, ?int $page): RedirectResponse
    {
        $query = $request->except('page') + ($page !== null && $page > 1 ? ['page' => $page] : []);

        return redirect()->to($request->url().($query ? '?'.http_build_query($query) : ''), 301);
    }
}
