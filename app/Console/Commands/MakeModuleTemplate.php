<?php

namespace App\Console\Commands;

use App\Models\ModuleTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleTemplate extends Command
{
    protected $signature = 'make:module-template
        {code : Код шаблону (буде перетворено на slug)}';

    protected $description = 'Створює шаблон модуля: клас, міграцію та порожній blade-шаблон';

    public function handle(): int
    {
        $code = $slug = Str::slug($this->argument('code'));
        $dummy = Str::ucfirst(Str::camel($slug));

        if (ModuleTemplate::where('code', $code)->exists()) {
            $this->error('Код шаблону вже зайнятий.');

            return self::FAILURE;
        }

        $this->createClass($code, $slug, $dummy);
        $this->createMigration($code, $slug, $dummy);
        $this->createView($slug);

        return self::SUCCESS;
    }

    private function createClass(string $code, string $slug, string $dummy): void
    {
        $path = app_path('Modules/'.$dummy.'Module.php');

        if (File::exists($path)) {
            $this->warn('Клас "App\Modules\\'.$dummy.'Module" вже існує');

            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->fromExample('Class.example', $code, $slug, $dummy));

        $this->info('Клас "App\Modules\\'.$dummy.'Module" створено');
    }

    private function createMigration(string $code, string $slug, string $dummy): void
    {
        $name = date('Y_m_d_His').'_add_'.$code.'_module_template.php';

        File::put(database_path('migrations/'.$name), $this->fromExample('Migration.example', $code, $slug, $dummy));

        $this->info('Міграцію "'.$name.'" створено');
    }

    private function createView(string $slug): void
    {
        $path = resource_path('views/modules/'.$slug.'.blade.php');

        if (File::exists($path)) {
            $this->warn('Шаблон "'.$path.'" вже існує');

            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');

        $this->info('Шаблон "modules/'.$slug.'.blade.php" створено');
    }

    private function fromExample(string $file, string $code, string $slug, string $dummy): string
    {
        return str_replace(
            ['{code-code}', '{code-slug}', '{code-dummy}'],
            [$code, $slug, $dummy],
            File::get(app_path('Console/Templates/ModuleTemplate/'.$file)),
        );
    }
}
