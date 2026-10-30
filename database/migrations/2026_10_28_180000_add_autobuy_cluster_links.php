<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const HEADING = 'Матеріали за темою';

    private const CLUSTER = [
        'avtomatychna-kupivlia-monet-nbu' => ['type' => Page::TYPE_SERVICE, 'url' => '/services/avtomatychna-kupivlia-monet-nbu', 'anchor' => 'Додавання монети в кошик у момент старту: як працює Numis'],
        'avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt' => ['type' => Page::TYPE_PAGE, 'url' => '/blog/avtokupivlia-monet-nbu-iak-tse-pratsiuye-i-komu-pidkhodyt', 'anchor' => 'Автокупівля монет НБУ: як це працює і кому підходить'],
        'bot-dlia-kupivli-monet-nbu-shcho-vmiye-numis-a-shcho-ni' => ['type' => Page::TYPE_PAGE, 'url' => '/blog/bot-dlia-kupivli-monet-nbu-shcho-vmiye-numis-a-shcho-ni', 'anchor' => 'Бот для купівлі монет НБУ: що вміє Numis, а що ні'],
        'kupivlia-monet-nbu-vruchnu-chy-avtomatychno-shcho-obraty' => ['type' => Page::TYPE_PAGE, 'url' => '/blog/kupivlia-monet-nbu-vruchnu-chy-avtomatychno-shcho-obraty', 'anchor' => 'Купівля монет НБУ вручну чи автоматично: що обрати'],
        'yak-avtomatyzuvaty-kupivliu-monet-nbu-sposoby' => ['type' => Page::TYPE_PAGE, 'url' => '/blog/yak-avtomatyzuvaty-kupivliu-monet-nbu-sposoby', 'anchor' => 'Як автоматизувати купівлю монет НБУ: п’ять способів'],
        'spovishchennia-chy-avtokupivlia-monet-nbu-riznytsia' => ['type' => Page::TYPE_PAGE, 'url' => '/blog/spovishchennia-chy-avtokupivlia-monet-nbu-riznytsia', 'anchor' => 'Сповіщення про монети НБУ чи автокупівля: у чому різниця'],
        'iak-vstyhnuty-kupyty-monetu-nbu-pershym' => ['type' => Page::TYPE_PAGE, 'url' => '/blog/iak-vstyhnuty-kupyty-monetu-nbu-pershym', 'anchor' => 'Як встигнути купити монету НБУ першим'],
    ];

    public function up(): void
    {
        foreach (self::CLUSTER as $slug => $own) {
            $page = Page::where('type', $own['type'])->where('slug', $slug)->first();

            if ($page === null || str_contains((string) $page->content, self::HEADING)) {
                continue;
            }

            $items = collect(self::CLUSTER)
                ->reject(fn (array $item, string $itemSlug): bool => $itemSlug === $slug)
                ->map(fn (array $item): string => '<li><a href="'.$item['url'].'">'.$item['anchor'].'</a></li>')
                ->implode("\n");

            $block = "\n<h2>".self::HEADING."</h2>\n<ul>\n{$items}\n</ul>";

            $page->forceFill(['content' => rtrim((string) $page->content).$block])->save();
        }
    }

    public function down(): void
    {
    }
};
