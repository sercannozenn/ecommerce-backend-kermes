<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDiscountTarget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_discount_id',
        'target_type',
        'target_id',
    ];

    public $timestamps = true;

    public function discount(): BelongsTo
    {
        return $this->belongsTo(ProductDiscount::class, 'product_discount_id');
    }
}
