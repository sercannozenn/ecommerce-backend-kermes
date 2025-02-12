<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'category_id',
        'tag_id',
        'user_id',
        'discount_percentage',
        'discount_fixed',
        'discount_start',
        'discount_end'
    ];


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
