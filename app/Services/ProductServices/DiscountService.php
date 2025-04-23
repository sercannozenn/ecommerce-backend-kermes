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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class DiscountService
{
    public function __construct(protected ProductDiscount              $model,
                                protected ProductDiscountTargetService $targetService
    )
    {
    }

    public function getPaginatedDiscounts(int $page = 1, int $limit = 10, array $filter = [], string $sortBy = 'id', string $sortOrder = 'asc'): array
    {
        $query = $this->model::query();

        if (!empty($filter['search'])) {
            $query->where(function ($q) use ($filter) {
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
            $query->where('is_active', (bool) $filter['is_active']);
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
     * @throws \Throwable
     */
    public function store(array $data): ProductDiscount
    {
        if ($this->hasConflict($data)) {
            throw ValidationException::withMessages([
                                                        'targets' => ['Seçilen hedef(ler) için aynı tarih aralığında tanımlı başka bir indirim zaten mevcut.']
                                                    ]);
        }

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

            if (in_array($discount->target_type, ['product', 'category', 'tag', 'brand'])) {
                $this->applyDiscount($discount);
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
        if ($this->hasConflict($data, $id)) {
            throw ValidationException::withMessages([
                                                        'targets' => ['Seçilen hedef(ler) için aynı tarih aralığında tanımlı başka bir indirim zaten mevcut.']
                                                    ]);
        }

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

            if (in_array($discount->target_type, ['product', 'category', 'tag', 'brand'])) {
                $this->applyDiscount($discount);
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

    public function applyDiscount(ProductDiscount $discount): void
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
            $basePrice = $priceRow->price_discount > 0 ? $priceRow->price_discount : $priceRow->price;
            $newDiscounted = $this->calculateDiscountedPrice($basePrice, $discount);

            ProductPriceHistory::where('product_id', $product->id)
                               ->where('is_closed', false)
                               ->update([
                                            'is_closed'   => true,
                                            'valid_until' => now(),
                                        ]);

            ProductPriceHistory::create([
                                            'product_id'             => $product->id,
                                            'product_price_id'       => $priceRow->id,
                                            'price'                  => $basePrice,
                                            'price_discount'         => $newDiscounted,
                                            'calculated_discount_id' => $discount->id,
                                            'is_closed'              => false,
                                            'valid_from'             => $discount->discount_start,
                                            'valid_until'            => $discount->discount_end,
                                        ]);
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
        return $this->getActiveDiscounts($product)->sortBy('priority')->first();
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
                           ->whereHas('targets', function ($q) use ($product) {
                               $q->where(function ($sub) use ($product) {
                                   $sub->where(function ($cond) use ($product) {
                                       $cond->where('target_type', 'product')
                                            ->where('target_id', $product->id);
                                   })
                                       ->orWhere(function ($cond) use ($product) {
                                           $cond->where('target_type', 'category')
                                                ->whereIn('target_id', $product->categories->pluck('id'));
                                       })
                                       ->orWhere(function ($cond) use ($product) {
                                           $cond->where('target_type', 'brand')
                                                ->where('target_id', $product->brand_id);
                                       })
                                       ->orWhere(function ($cond) use ($product) {
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


}
