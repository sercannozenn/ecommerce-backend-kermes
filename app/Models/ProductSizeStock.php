<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSizeStock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'size',
        'stock',
        'stock_alert'
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
