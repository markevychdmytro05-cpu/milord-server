<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Заявка з форми зв'язку на сайті.
 */
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use CrudTrait, HasFactory;

    protected $fillable = ['name', 'contact', 'message', 'source_url', 'ip', 'is_processed'];

    protected function casts(): array
    {
        return ['is_processed' => 'boolean'];
    }
}
