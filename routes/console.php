<?php

use App\Models\User;
use App\Support\ResponseSigner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('licenses:remind-expiring')->dailyAt('09:00')->withoutOverlapping(120)->onOneServer();

Artisan::command('license:keygen', function () {
    $pair = ResponseSigner::generateKeyPair();

    $this->line('Додайте в .env сервера (тримайте в секреті):');
    $this->line('LICENSE_SIGNING_SECRET_KEY='.$pair['secret']);
    $this->newLine();
    $this->line('Публічний ключ для перевірки підпису в клієнті:');
    $this->line($pair['public']);
})->purpose('Згенерувати пару ключів Ed25519 для підпису відповідей API');

Artisan::command('license:doctor', function (): int {
    $healthy = true;

    try {
        if (ResponseSigner::sign('license:doctor') === null) {
            $this->error('Ключ підпису не задано. Перед production виконайте license:keygen і налаштуйте LICENSE_SIGNING_SECRET_KEY.');
            $healthy = false;
        } else {
            $this->info('Підпис Ed25519 працює.');
        }
    } catch (RuntimeException $exception) {
        $this->error($exception->getMessage());
        $healthy = false;
    }

    foreach (['offline_grace_hours', 'rate_limit'] as $setting) {
        if (config('license.'.$setting) < 1) {
            $this->error('license.'.$setting.' має бути більшим за нуль.');
            $healthy = false;
        }
    }

    return $healthy ? 0 : 1;
})->purpose('Перевірити конфігурацію ліцензій перед запуском у production');

Artisan::command('admin:create {email} {--name=Admin} {--manager : Створити менеджера замість адміністратора}', function (string $email) {
    $password = $this->secret('Пароль (мінімум 8 символів)');

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'string', 'min:8']],
    );

    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }

    User::create([
        'name' => $this->option('name'),
        'email' => $email,
        'password' => $password,
        'is_active' => true,
    ])->assignRole($this->option('manager') ? User::ROLE_MANAGER : User::ROLE_ADMIN);

    $this->info("Користувача {$email} створено.");
})->purpose('Створити користувача адмінки');
