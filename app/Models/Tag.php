<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method static create(array $data)
 */
class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_tag');
    }
}

