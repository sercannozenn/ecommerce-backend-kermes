<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDiscount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'target_type',
        'discount_type',
        'discount_amount',
        'priority',
        'is_active',
        'discount_start',
        'discount_end',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'priority'        => 'integer',
        'discount_amount' => 'float',
        'discount_start'  => 'datetime',
        'discount_end'    => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    // 🔗 Hedef ilişkileri
    public function targets(): HasMany
    {
        return $this->hasMany(ProductDiscountTarget::class, 'product_discount_id');
    }

    // 🔗 İndirim uygulandığında oluşturulan fiyat geçmişleri
    public function histories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class, 'calculated_discount_id');
    }
}
