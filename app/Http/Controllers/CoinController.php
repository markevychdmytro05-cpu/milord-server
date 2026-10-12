<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Support\PaginationRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CoinController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $sort = $request->query('sort') === 'desc' ? 'desc' : 'asc';
        $months = Coin::published()->withImage()->whereNotNull('release_year')->whereNotNull('release_month')
            ->select('release_year', 'release_month')->distinct()->orderBy('release_year')->orderBy('release_month')->get()
            ->pipe(function ($coins) {
                $severalYears = $coins->pluck('release_year')->unique()->count() > 1;

                return $coins->mapWithKeys(fn (Coin $coin): array => [sprintf('%d-%02d', $coin->release_year, $coin->release_month) => Coin::months()[$coin->release_month].($severalYears ? ' '.$coin->release_year : '')]);
            });
        $month = $months->has($request->query('month')) ? $request->query('month') : null;

        $query = Coin::published()->withImage()->chronological($sort);
        if ($month) {
            [$year, $number] = explode('-', $month);
            $query->where('release_year', (int) $year)->where('release_month', (int) $number);
        }
        $coins = $query->paginate(12)->onEachSide(1)->withQueryString();

        return PaginationRedirect::fromFirstPage($request)
            ?? PaginationRedirect::fromOutOfRange($request, $coins)
            ?? view('public.coins', ['coins' => $coins, 'sort' => $sort, 'months' => $months, 'month' => $month]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        if ($slug !== strtolower($slug)) {
            return redirect()->route('coins.show', strtolower($slug), 301);
        }

        $coin = Coin::published()->where('slug', $slug)->firstOrFail();

        $related = Coin::published()->withImage()->where('id', '!=', $coin->id)
            ->orderByRaw('COALESCE(sale_starts_at, created_at) DESC')->limit(4)->get();

        return view('public.coin', ['coin' => $coin, 'related' => $related]);
    }
}
