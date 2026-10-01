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
        $coins = Coin::published()->chronological($sort)->paginate(12)->onEachSide(1)->withQueryString();

        return PaginationRedirect::fromFirstPage($request)
            ?? PaginationRedirect::fromOutOfRange($request, $coins)
            ?? view('public.coins', ['coins' => $coins, 'sort' => $sort]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        if ($slug !== strtolower($slug)) {
            return redirect()->route('coins.show', strtolower($slug), 301);
        }

        $coin = Coin::published()->where('slug', $slug)->firstOrFail();

        $related = Coin::published()->where('id', '!=', $coin->id)
            ->orderByRaw('COALESCE(sale_starts_at, created_at) DESC')->limit(4)->get();

        return view('public.coin', ['coin' => $coin, 'related' => $related]);
    }
}
