<?php

namespace App\Services\ProductServices;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductPriceHistory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class DiscountService
{
    protected string $message = '';
    public function __construct(protected ProductDiscount              $model,
                                protected ProductDiscountTargetService $targetService)
    {
    }

    public function getPaginatedDiscounts(int $page = 1, int $limit = 10, array $filter = [], string $sortBy = 'id', string $sortOrder = 'asc'): array
    {
        $query = $this->model::query();

        if (!empty($filter['search'])) {
            $query->where(function ($q) use ($filter)
            {
                $q->where('name', 'like', '%' . $filter['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filter['search'] . '%');
            });
        }

        if (!empty($filter['target_type'])) {
            $query->where('target_type', $filter['target_type']);
        }

        if (!empty($filter['discount_type'])) {
            $query->where('discount_type', $filter['discount_type']);
        }

        if ($filter['is_active'] !== '') {
            $query->where('is_active', (bool)$filter['is_active']);
        }

        $query->orderBy($sortBy, $sortOrder);

        $discounts = $query->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $discounts->items(),
            'total'        => $discounts->total(),
            'current_page' => $discounts->currentPage(),
            'last_page'    => $discounts->lastPage(),
        ];
    }


    public function searchTargets(string $type, ?string $query = null): array
    {
        $modelMap = [
            'product'  => Product::class,
            'category' => Category::class,
            'brand'    => Brand::class,
            'tag'      => Tag::class,
            'user'     => User::class,
        ];

        $model = $modelMap[$type] ?? null;

        if (!$model) {
            return [];
        }

        $q = $model::query();

        // Tüm aramalarda aktif kayıtlar gelsin
        if (Schema::hasColumn((new $model)->getTable(), 'is_active')) {
            $q->where('is_active', true);
        }

        if ($type === 'user') {
            $q->where(function ($sub) use ($query)
            {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            });
        } else {
            $q->where('name', 'like', "%{$query}%");
        }

        return $q->limit(20)->get(['id', 'name'])->toArray();
    }

    /**
     * @throws Throwable
     */
    public function store(array $data): ProductDiscount
    {
        DB::beginTransaction();

        try {
            $discount = $this->model::create([
                                                 'name'            => $data['name'],
                                                 'description'     => $data['description'] ?? null,
                                                 'target_type'     => $data['target_type'],
                                                 'discount_type'   => $data['discount_type'],
                                                 'discount_amount' => $data['discount_amount'],
                                                 'priority'        => $data['priority'] ?? 0,
                                                 'is_active'       => $data['is_active'] ?? true,
                                                 'discount_start'  => $data['discount_start'],
                                                 'discount_end'    => $data['discount_end'],
                                             ]);

            $this->targetService
                ->createTargets($discount->id, $data['target_type'], $data['targets']);

            if (in_array($discount->target_type, ['product', 'category', 'tag', 'brand']) &&
                $this->shouldApplyDiscount($discount))
            {
                \Log::info("discount" . $discount->name);
                $this->applyDiscount($discount, 'İndirim oluşturuldu');
            }

            DB::commit();
            return $discount;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function update(int $id, array $data): ProductDiscount
    {
        DB::beginTransaction();

        try {
            $discount = $this->model::findOrFail($id);

            $discount->update([
                                  'name'            => $data['name'],
                                  'description'     => $data['description'] ?? null,
                                  'target_type'     => $data['target_type'],
                                  'discount_type'   => $data['discount_type'],
                                  'discount_amount' => $data['discount_amount'],
                                  'priority'        => $data['priority'] ?? 0,
                                  'is_active'       => $data['is_active'] ?? true,
                                  'discount_start'  => $data['discount_start'],
                                  'discount_end'    => $data['discount_end'],
                              ]);

            $this->targetService->syncTargets($discount, $data['targets']);

            if (in_array($discount->target_type, ['product', 'category', 'tag', 'brand']) && $this->shouldApplyDiscount($discount, true)) {
                $this->applyDiscount($discount, 'İndirim güncellendi.');
            }

            DB::commit();
            return $discount;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function hasConflict(array $data, ?int $excludeId = null): bool
    {
        $targetIds  = Arr::get($data, 'targets', []);
        $targetType = Arr::get($data, 'target_type');
        $start      = Arr::get($data, 'discount_start');
        $end        = Arr::get($data, 'discount_end');

        return $this->targetService
            ->getQueryForConflictCheck($targetIds, $targetType, $start, $end, $excludeId)
            ->exists();
    }

    /**
     * Yeni indirimin price history e uygulanıp uygulanmayacağını kontrol eder.
     *
     * @param  ProductDiscount  $discount
     * @param  bool             $excludeSelf  (update sırasında kendisini hariç tutmak için)
     * @return bool
     */
    private function shouldApplyDiscount(ProductDiscount $discount, bool $excludeSelf = false): bool
    {
        // Aynı tipte, aynı hedef(ler) için
        $query = $this->model::query()
                             ->where('target_type', $discount->target_type)
                             ->where('discount_start', '<=', $discount->discount_end)
                             ->where('discount_end',   '>=', $discount->discount_start)
                             ->where('is_active', true);

        if ($excludeSelf) {
            $query->where('id', '!=', $discount->id);
        }

        // pivot tablodan eşleşen target_id'lere bakıyoruz
        $targetIds = $discount->targets->pluck('target_id')->toArray();
        $query->whereHas('targets', function($q) use($targetIds, $discount) {
            $q->where('target_type', $discount->target_type)
              ->whereIn('target_id', $targetIds);
        });

        // Burada en yüksek önceliği alıyoruz
        $maxPriority = (int) $query->max('priority');

        // Yeni indirim önceliği en yüksek veya eşit mi?
        return $discount->priority >= $maxPriority;
    }

    /**
     * @throws Throwable
     */
    public function applyDiscount(ProductDiscount $discount, string $reason): void
    {
        $targetIds = $discount->targets->pluck('target_id');
        $type      = $discount->target_type;

        $productQuery = Product::query()->with('latestPrice');

        switch ($type) {
            case 'product':
                $productQuery->whereIn('id', $targetIds);
                break;
            case 'category':
                $productQuery->whereHas('categories', fn($q) => $q->whereIn('categories.id', $targetIds));
                break;
            case 'tag':
                $productQuery->whereHas('tags', fn($q) => $q->whereIn('tags.id', $targetIds));
                break;
            case 'brand':
                $productQuery->whereIn('brand_id', $targetIds);
                break;
            default:
                return;
        }

        $products = $productQuery->get();

        foreach ($products as $product) {
            $priceRow = $product->latestPrice;
            if (!$priceRow)
                continue;
            $basePrice     = $priceRow->price_discount > 0 ? $priceRow->price_discount : $priceRow->price;
            $newDiscounted = $this->calculateDiscountedPrice($basePrice, $discount);

            $last = ProductPriceHistory::query()
                                       ->where('product_id', $product->id)
                                       ->where('is_closed', false)
                                       ->orderBy('valid_from', 'desc')
                                       ->first();

            if ($last
                && $last->calculated_discount_id === $discount->id
                && (float)$last->price === (float)$basePrice
                && (float)$last->price_discount === (float)$newDiscounted
            ) {
                // Aynı discount_id, price ve price_discount ise atla
                Log::info('Aynı discount_id, price ve price_discount ise atla: ');
                continue;
            }

            // Tarih aralığına göre closed çekilmeli.
            // Yani eğer indirimin tarihi hemen şu an başlıyorsa is_closed true olmalı ancak hemen başlamıyorsa bu işlemi command yapmalı.
            app(ProductPriceHistoryService::class)->createHistory($product, $priceRow, $reason);
        }
    }

    private function calculateDiscountedPrice($original, ProductDiscount $discount): float
    {
        return $discount->discount_type === 'percentage' ? $original * (1 - $discount->discount_amount / 100) : max($original - $discount->discount_amount, 0);
    }

    public function getDiscountedPriceAsFloat(Product $product, float $price): float
    {
        $discounts = $this->getActiveDiscounts($product);

        $finalPrice = $price;

        foreach ($discounts as $discount) {
            $calculated = $this->calculateDiscountedPrice($price, $discount);
            $finalPrice = min($finalPrice, $calculated);
        }

        return max($finalPrice, 1); // 1 TL altına düşmesin
    }

    public function getActiveDiscount(Product $product): ?ProductDiscount
    {
        return $this->getActiveDiscounts($product)->sortByDesc('priority')->first();
    }

    /**
     * @param Product $product
     *
     * @return Collection
     */
    public function getActiveDiscounts(Product $product): \Illuminate\Support\Collection
    {
        $now = now();

        return $this->model::query()
                           ->where('discount_start', '<=', $now)
                           ->where('discount_end', '>=', $now)
                           ->whereHas('targets', function ($q) use ($product)
                           {
                               $q->where(function ($sub) use ($product)
                               {
                                   $sub->where(function ($cond) use ($product)
                                   {
                                       $cond->where('target_type', 'product')
                                            ->where('target_id', $product->id);
                                   })
                                       ->orWhere(function ($cond) use ($product)
                                       {
                                           $cond->where('target_type', 'category')
                                                ->whereIn('target_id', $product->categories->pluck('id'));
                                       })
                                       ->orWhere(function ($cond) use ($product)
                                       {
                                           $cond->where('target_type', 'brand')
                                                ->where('target_id', $product->brand_id);
                                       })
                                       ->orWhere(function ($cond) use ($product)
                                       {
                                           $cond->where('target_type', 'tag')
                                                ->whereIn('target_id', $product->tags->pluck('id'));
                                       });
                               });
                           })
                           ->where('is_active', true)
                           ->get();
    }

    public function setDiscount(ProductDiscount $discount): self
    {
        $this->model = $discount;
        return $this;
    }

    /**
     * @return array{discount: ProductDiscount,  message: string|null}
     * @throws Throwable
     */
    public function changeStatus(): array
    {
        DB::beginTransaction();
        try{
            // 1) Toggle durumu (aktif <-> pasif)
        $newStatus = !($this->model->is_active);
        // -----------------------------
        // 2) Aktifleniyorsa: çakışma ve tarih kontrolleri
        // -----------------------------
        \Log::info("Bilgi: " . intval($newStatus));
        \Log::info("Bilgi2: " . $this->model->name);
        \Log::info("Bilgi3: " . strval($this->model->is_active));
        if ($newStatus) {
            // Tarih kontrolü Tarih kontrolü
            $now   = now();
            $start = $this->model->discount_start;
            $end   = $this->model->discount_end;

            if ($now->lt($start)) {
                throw ValidationException::withMessages([
                                                            'discount' => [
                                                                "Bu indirim {$start->format('d.m.Y H:i')} tarihinde başlayacak. Şu an aktif edemezsiniz."
                                                            ]
                                                        ]);
            }

            if ($now->gt($end)) {
                throw ValidationException::withMessages([
                                                            'discount' => [
                                                                "Bu indirim {$end->format('d.m.Y H:i')} tarihinde sona ermiş. Süre dolduğu için tekrar aktif edemezsiniz."
                                                            ]
                                                        ]);
            }


            // Öncelik kontrolü (çakışma dahil)
            $shouldApply = $this->shouldApplyDiscount($this->model, true);

            if (! $shouldApply) {
                $this->message =
                    "Daha yüksek öncelikli başka indirim(ler) olduğu için fiyat tarihçesine yansıtılmadı.";
            }


        }

        // -----------------------------
        // 3) Durum güncelleme
        // -----------------------------
        $this->model->update(['is_active' => $newStatus]);

        // -----------------------------
        // 4) History’e yansıtma
        // -----------------------------
        if ($newStatus) {
            // 4.1) Aktive alındığında indirimi uygula
            if (! empty($shouldApply)) {
                $this->applyDiscount($this->model, "İndirimin durumu değiştirildi. İndirim Adı: " . $this->model->name . ', Yeni İndirim Durumu: Aktif');
            }
        } else {
            // 4.2) Pasife alındığında revert işlemi
            $historyService = app()->make(ProductPriceHistoryService::class);
            $historyService->revertDiscount($this->model, "İndirimin durumu değiştirildi. İndirim Adı: " . $this->model->name . ', Yeni İndirim Durumu: Pasif');
        }

        DB::commit();

        return [
            'discount' => $this->model,
            'message' => $this->message
        ];
        }
        catch (\Exception $exception){
            DB::rollBack();
            throw $exception;
        }
    }

    public function getAffectedProducts(ProductDiscount $discount): \Illuminate\Support\Collection
    {
        $targetIds = $discount->targets->pluck('target_id')->toArray();
        $query     = Product::query();

        switch ($discount->target_type) {
            case 'product':
                $query->whereIn('id', $targetIds);
                break;
            case 'category':
                $query->whereHas('categories', fn($q) =>
                $q->whereIn('categories.id', $targetIds)
                );
                break;
            case 'tag':
                $query->whereHas('tags', fn($q) =>
                $q->whereIn('tags.id', $targetIds)
                );
                break;
            case 'brand':
                $query->whereIn('brand_id', $targetIds);
                break;
            default:
                return collect();
        }

        return $query->get()->map(function (Product $product) use ($discount) {
            $histories = ProductPriceHistory::query()
                                            ->where('product_id', $product->id)
                                            ->where('calculated_discount_id', $discount->id)
                                            ->orderBy('valid_from')
                                            ->get(['price','price_discount','valid_from','valid_until'])
                                            ->map(fn($h) => [
                                                'price'          => round($h->price, 2),
                                                'price_discount' => round($h->price_discount, 2),
                                                'from'           => $h->valid_from->toDateTimeString(),
                                                'until'          => $h->valid_until?->toDateTimeString(),
                                            ]);

            return [
                'id'        => $product->id,
                'name'      => $product->name,
                'histories' => $histories,
            ];
        });
    }
}
