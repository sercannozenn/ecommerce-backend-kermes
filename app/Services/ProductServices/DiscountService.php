<?php

namespace App\Services\ProductServices;

use App\Models\Product;
use App\Models\ProductDiscount;

class DiscountService
{
    public function __construct(protected ProductDiscount $model) {}

    public function getDiscountedPrice(Product $product): string
    {
        $basePrice = $product->latestPrice->price_discount > 0
            ? $product->latestPrice->price_discount
            : $product->latestPrice->price;

        $now = now();

        $query = $this->model::query()
                             ->where('discount_start', '<=', $now)
                             ->where('discount_end', '>=', $now)
                             ->where(function ($q) use ($product) {
                                 $q->orWhere('product_id', $product->id)
                                   ->orWhereIn('category_id', $product->categories->pluck('id') ?? [])
//                                   ->orWhere('brand_id', $product->brand_id)
                                   ->orWhereIn('tag_id', $product->tags->pluck('id') ?? []);

                                 if (auth()->check()) {
                                     $q->orWhere('user_id', auth()->id());
                                 }
                             });

        $discounts = $query->get();
        $finalPrice = $basePrice;

        foreach ($discounts as $discount) {
            if ($discount->discount_percentage) {
                $discounted = $basePrice * (1 - $discount->discount_percentage / 100);
                $finalPrice = min($finalPrice, $discounted);
            }

            if ($discount->discount_fixed) {
                $discounted = max($basePrice - $discount->discount_fixed, 0);
                $finalPrice = min($finalPrice, $discounted);
            }
        }

        // İndirimli fiyat 1 TL altına düşerse, 1 TL yap
        return number_format(max($finalPrice, 1), 2, ',', '');
    }

}
