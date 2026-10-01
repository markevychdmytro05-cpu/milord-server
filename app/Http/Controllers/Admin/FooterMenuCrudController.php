<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;

class FooterMenuCrudController extends MenuCrudController
{
    protected function menu(): array
    {
        return [MenuItem::FOOTER, 'footer-menu', 'меню підвалу'];
    }
}
