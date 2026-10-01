<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;

class HeaderMenuCrudController extends MenuCrudController
{
    protected function menu(): array
    {
        return [MenuItem::HEADER, 'header-menu', 'меню шапки'];
    }
}
