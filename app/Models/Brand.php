<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property boolean $is_active
 */
class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'is_active', 'keywords', 'seo_description', 'author'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
