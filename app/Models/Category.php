<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use CrudTrait;

    protected $fillable = ['name'];

    protected static function booted(): void
    {
        static::updated(function (Category $category): void {
            if ($category->wasChanged('name')) {
                Page::where('category', $category->getOriginal('name'))->update(['category' => $category->name]);
            }
        });
    }

    public function pagesCount(): int
    {
        return Page::where('category', $this->name)->count();
    }

    public static function asSelectArray(): array
    {
        return self::orderBy('name')->pluck('name', 'name')->all();
    }
}
