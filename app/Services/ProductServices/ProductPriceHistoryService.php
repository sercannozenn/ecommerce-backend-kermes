<?php

namespace App\Services\ProductServices;

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductPrice;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductPriceHistoryService
{
    public function __construct(protected ProductPriceHistory $model,
                                protected DiscountService $discountService)
    {
    }

    /**
     * @throws Throwable
     */
    public function createHistory(Product $product, ProductPrice $price): ProductPriceHistory
    {
        DB::beginTransaction();
        try {
            // Mevcut aktif geçmişi kapat
            $this->model::query()
                        ->where('product_id', $product->id)
                        ->where('is_closed', false)
                        ->update([
                                     'is_closed'   => true,
                                     'valid_until' => now(),
                                 ]);

            $basePrice = $price->price_discount > 0 ? $price->price_discount : $price->price;

            // Geçerli indirimi hesapla
            $discounted = $this->discountService->getDiscountedPriceAsFloat($product, $basePrice);
            $activeDiscount = $this->discountService->getActiveDiscount($product);

            // Yeni geçmiş kaydını oluştur
            $history = $this->model::create([
                                                'product_id'             => $product->id,
                                                'product_price_id'       => $price->id,
                                                'price'                  => $basePrice,
                                                'price_discount'         => $discounted,
                                                'calculated_discount_id' => $activeDiscount?->id,
                                                'is_closed'              => false,
                                                'valid_from'             => now(),
                                                'valid_until'            => null,
                                            ]);

            DB::commit();
            return $history;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
