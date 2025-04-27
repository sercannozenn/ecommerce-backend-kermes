<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    protected $fillable = [
        'product_id',
        'product_price_id',
        'calculated_discount_id',
        'price',
        'price_discount',
        'is_closed',
        'valid_from',
        'valid_until',
        'updated_by',
        'reason',
    ];

    protected $casts = [
        'is_closed'   => 'boolean',
        'valid_from'  => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(ProductDiscount::class, 'calculated_discount_id');
    }
}

