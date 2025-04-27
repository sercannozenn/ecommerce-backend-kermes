<?php

namespace App\Services\ProductServices;

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductPrice;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductPriceHistoryService
{
    public function __construct(protected ProductPriceHistory $model,
                                protected DiscountService $discountService)
    {
    }

    public function setHistory(ProductPriceHistory $history): self
    {
        $this->model = $history;
        return $this;
    }

    /**
     * @throws Throwable
     */
    public function createHistory(Product $product, ProductPrice $price, string $reason): ProductPriceHistory
    {
        DB::beginTransaction();
        try {
            // Mevcut aktif geçmişi kapat

            $basePrice = $price->price_discount > 0 ? $price->price_discount : $price->price;

            // Geçerli indirimi hesapla
            $discounted = $this->discountService->getDiscountedPriceAsFloat($product, $basePrice);
            $activeDiscount = $this->discountService->getActiveDiscount($product);
            $discountId     = $activeDiscount?->id;


            $last = $this->model::query()
                                ->where('product_id', $product->id)
                                ->where('is_closed', false)
                                ->orderBy('valid_from', 'desc')
                                ->first();
            \Log::info('last: ' . $last?->price_discount . ' - ' . $last?->price);
            \Log::info('base: ' . $basePrice);
            \Log::info('discounted: ' . $discounted);

            // 4) Eğer son kayıt aynı indirim ve fiyat bilgilerine sahipse, hiçbir şey yapma
            if (
                $last
                && (float)$last->price === (float)$basePrice
                && (float)$last->price_discount === (float)$discounted
                && $last->calculated_discount_id === $discountId
            ) {
                DB::rollBack();
                return $last;
            }

            $this->model::query()
                        ->where('product_id', $product->id)
                        ->where('is_closed', false)
                        ->update([
                                     'is_closed'   => true,
                                     'valid_until' => now(),
                                 ]);


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
                                                'updated_by'             => auth()->id(),
                                                'reason'                 => $reason,
                                            ]);

            DB::commit();
            return $history;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function revertDiscount(ProductDiscount $discount, string $reason): void
    {
        // 1) Etkilenen ürünleri sorgula
        $targetIds = $discount->targets->pluck('target_id')->toArray();
        $type      = $discount->target_type;

        $productQuery = Product::query()->with('latestPrice');

        switch ($type) {
            case 'product':
                $productQuery->whereIn('id', $targetIds);
                break;
            case 'category':
                $productQuery->whereHas('categories', fn($q) =>
                $q->whereIn('categories.id', $targetIds)
                );
                break;
            case 'tag':
                $productQuery->whereHas('tags', fn($q) =>
                $q->whereIn('tags.id', $targetIds)
                );
                break;
            case 'brand':
                $productQuery->whereIn('brand_id', $targetIds);
                break;
            default:
                return;
        }

        $products = $productQuery->get();

        // 2) Her ürün için createHistory ile yeni state’i oluştur
        foreach ($products as $product) {
            $priceRow = $product->latestPrice;
            if (! $priceRow) {
                continue;
            }

            // createHistory zaten açık kayıtları kapatıp
            // aktif indirim veya baz fiyatı history’e ekliyor
            $this->createHistory($product, $priceRow, $reason);
        }
    }

    public function getHistory(Product $product): Collection
    {
        return $this->model::query()
                           ->where('product_id', $product->id)
                           ->orderBy('valid_from')
            // İndirimin adını almak için eager load
                           ->with([
                               'discount:id,name',
                               'updatedBy:id,name'
                                  ])
                           ->get(['price','price_discount','calculated_discount_id','valid_from','valid_until', 'updated_by', 'reason'])
                           ->map(fn($h) => [
                               'price'            => round($h->price, 2),
                               'price_discount'   => round($h->price_discount, 2),
                               'discount_name'    => $h->discount?->name ?? 'İndirimsiz',
                               'from'             => $h->valid_from->toDateTimeString(),
                               'until'            => $h->valid_until?->toDateTimeString(),
                               'updated_by'       => $h->updatedBy?->name,
                               'reason'           => $h->reason,
                           ]);
    }


}
