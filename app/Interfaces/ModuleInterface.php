<?php

namespace App\Interfaces;

interface ModuleInterface
{
    /**
     * Повертає HTML модуля.
     *
     * @param  array<string, mixed>  $settings  Налаштування модуля (JSON-колонка setting).
     */
    public function draw(array $settings): string;
}
