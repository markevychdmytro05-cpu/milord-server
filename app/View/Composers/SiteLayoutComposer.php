<?php

namespace App\View\Composers;

use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SiteLayoutComposer
{
    public function compose(View $view): void
    {
        $site = SiteSetting::current();
        [$groups, $loose] = MenuItem::tree(MenuItem::FOOTER)->partition(fn (MenuItem $item): bool => $item->tree_children->isNotEmpty());

        $view->with([
            'site' => $site,
            'headerMenu' => MenuItem::tree(MenuItem::HEADER),
            'footerNav' => $loose->values(),
            'footerColumns' => $this->columns($groups),
            'messengers' => $site->messengers(),
            'footerModules' => $site->drawModules(),
        ]);
    }

    /**
     * Групи підвалу (пункти з вкладеними) стають колонками лінків.
     *
     * @param  Collection<int, MenuItem>  $groups
     * @return Collection<int, array{title: string, items: Collection<int, MenuItem>}>
     */
    private function columns(Collection $groups): Collection
    {
        return $groups->map(fn (MenuItem $item): array => ['title' => $item->label, 'items' => $item->tree_children])->values();
    }
}
