<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/** Файл-підтвердження володіння сайтом для IndexNow: /{ключ}.txt віддає сам ключ. */
class IndexNowKeyController extends Controller
{
    public function __invoke(string $key): Response
    {
        $configured = (string) config('services.indexnow.key');

        abort_unless($configured !== '' && hash_equals($configured, $key), 404);

        return response($configured, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
