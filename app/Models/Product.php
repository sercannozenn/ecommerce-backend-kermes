<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static create(array $data)
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'gender',
        'brand_id',
        'short_description',
        'long_description',
        'is_active',
        'view_count',
        'keywords',
        'seo_description',
        'author'
    ];

    protected $appends = ['final_price', 'price'];

    protected $casts = [
        'gender' => \App\Enums\Gender::class,
    ];
    public function getFinalPriceAttribute()
    {
        return $this->activePriceHistory?->price_discount ?? $this->latestPrice?->price_discount ?? $this->latestPrice?->price;
    }
    public function getPriceAttribute()
    {
        return $this->activePriceHistory?->price ??  $this->latestPrice?->price ?? $this->latestPrice?->price_discount;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class)->latestOfMany();
    }
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }
    public function activePriceHistory(): HasOne
    {
        return $this->hasOne(ProductPriceHistory::class)
                    ->where('is_closed', false)
                    ->latest();
    }
    public function publishHistory(): HasMany
    {
        return $this->hasMany(ProductPublishHistory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function featuredImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_featured', true);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variants', 'product_id', 'variant_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSizeStock::class);
    }


    protected static function booted(): void
    {
        static::deleting(fn($product) => $product->sizes()->delete());
        static::restoring(fn($product) => $product->sizes()->withTrashed()->restore());
    }
}
