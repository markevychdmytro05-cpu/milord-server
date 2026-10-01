<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SuperAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super
        {email? : Email супердаміна}
        {--name= : Ім\'я}
        {--password= : Пароль (якщо не вказано – запитається)}';

    protected $description = 'Створює (або оновлює) користувача адмінки з роллю admin і всіма правами';

    public function handle(SuperAdminService $service): int
    {
        $email = $this->argument('email') ?? text('Email', required: true);
        $name = $this->option('name') ?? Str::before($email, '@');

        $validator = Validator::make(['email' => $email, 'name' => $name], [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $exists = User::where('email', $email)->exists();
        $plain = $this->option('password') ?? ($exists ? null : password('Пароль (мін. 8 символів)', required: true));

        if ($plain !== null && strlen($plain) < 8) {
            $this->error('Пароль має бути не коротший за 8 символів.');

            return self::FAILURE;
        }

        $user = $service->grant($email, $name, $plain);

        $this->info(($exists ? 'Оновлено' : 'Створено')." супердаміна {$user->email} з правами: {$user->permissions->count()}.");

        return self::SUCCESS;
    }
}
