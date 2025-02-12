<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price',
        'discount_price',
        'discount_start',
        'discount_end',
        'updated_by'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
