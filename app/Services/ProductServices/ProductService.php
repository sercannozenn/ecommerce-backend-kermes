<?php

namespace App\Services\ProductServices;

use App\Models\Category;
use App\Models\Product;
use App\Services\BaseService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductService extends BaseService
{
    public function __construct(private Product $model)
    {
    }

    public function getAll(): Collection
    {
        return $this->model->with(['categories', 'tags', 'prices', 'discounts', 'images', 'variants'])->get();
    }

    public function getPaginatedProducts(int $page = 1, int $limit = 10, array $filter = [], string $sortBy = 'id', string $sortOrder = 'desc'): array
    {
        $query            = $this->model::query();
        $search           = $filter['search'] ?? '';
        $tags             = $filter['tags'] ?? [];
        $categories       = $filter['categories'] ?? [];
        $brands           = $filter['brands'] ?? [];
        $genders          = $filter['genders'] ?? [];
        $minPrice         = $filter['min_price'] ?? null;
        $maxPrice         = $filter['max_price'] ?? null;
        $minPriceDiscount = $filter['min_price_discount'] ?? null;
        $maxPriceDiscount = $filter['max_price_discount'] ?? null;
        $minFinalPrice    = $filter['min_price_discount'] ?? null;
        $maxFinalPrice    = $filter['max_price_discount'] ?? null;

        \Log::info('Filter:', ['filter' => $filter]);

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('long_description', 'like', "%{$search}%");

            $query->orWhereHas('categories', function ($categoryQuery) use ($search)
            {
                $categoryQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('description', 'like', "%{$search}%")
                              ->orWhere('slug', 'like', "%{$search}%");
            });
            $query->orWhereHas('brand', function ($categoryQuery) use ($search)
            {
                $categoryQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('slug', 'like', "%{$search}%");
            });

            $query->orWhereHas('tags', function ($tagQuery) use ($search)
            {
                $tagQuery->where('name', 'like', "%{$search}%")
                         ->orWhere('slug', 'like', "%{$search}%");
            });

            $query->orWhereHas('latestPrice', function ($priceQuery) use ($search)
            {
                $priceQuery->where('price', 'like', "%{$search}%")
                           ->orWhere('price_discount', 'like', "%{$search}%");
            });
        }

        if (!empty($categories))
        {
            $query->whereHas('categories', function ($categoryQuery) use ($categories)
            {
                $categoryQuery->whereIn('category_id', $categories)
                              ->orWhereIn('slug', $categories);
            });
        }

        if (!empty($brands))
        {
            $query->where(function ($query) use ($brands){
                $query->whereHas('brand', function ($brandQuery) use ($brands)
                {
                    $brandQuery->whereIn('brand_id', $brands)
                              ->orWhereIn('slug', $brands);
                });
            });
//            $query->whereIn('brand_id', $brands);
        }

        if (!empty($genders))
        {
            $query->whereIn('gender', $genders);
        }

        if (!empty($tags))
        {
            $query->whereHas('tags', function ($tagQuery) use ($tags)
            {
                $tagQuery->whereIn('tag_id', $tags);
            });
        }
        if (!empty($minPrice))
        {
            $query->whereHas('latestPrice', function ($priceQuery) use ($minPrice)
            {
                $priceQuery->where('price', '>=', $minPrice);
            });
        }
        if (!empty($maxPrice))
        {
            $query->whereHas('latestPrice', function ($priceQuery) use ($maxPrice)
            {
                $priceQuery->where('price', '<=', $maxPrice);
            });
        }
        if (!empty($minPriceDiscount))
        {
            $query->whereHas('latestPrice', function ($priceQuery) use ($minPriceDiscount)
            {
                $priceQuery->where('price_discount', '>=', $minPriceDiscount);
            });
        }
        if (!empty($maxPriceDiscount))
        {
            $query->whereHas('latestPrice', function ($priceQuery) use ($maxPriceDiscount)
            {
                $priceQuery->where('price_discount', '<=', $maxPriceDiscount);
            });
        }
        if (!empty($minFinalPrice)) {
            $query->whereHas('activePriceHistory', fn($q) => $q->where('price_discount', '>=', $minFinalPrice));
        }

        if (!empty($maxFinalPrice)) {
            $query->whereHas('activePriceHistory', fn($q) => $q->where('price_discount', '<=', $maxFinalPrice));
        }



        if ($sortBy === 'price'){
            $query->leftJoin('product_prices', function ($join) {
                $join->on('product_prices.product_id', '=', 'products.id')
                     ->whereRaw('product_prices.id = (SELECT MAX(id) FROM product_prices WHERE product_prices.product_id = products.id)');
            });
            $sortBy = 'product_prices.price';
        }
        if ($sortBy === 'final_price') {
            \Log::info("Final Price: ", ['sortBy' => $sortBy, 'order' => $sortOrder]);;;
            $query->leftJoin('product_price_histories', function ($join) {
                $join->on('product_price_histories.product_id', '=', 'products.id')
                     ->where('product_price_histories.is_closed', false);
            });

            $sortBy = 'product_price_histories.price_discount';
        }

        if ($sortBy ==='id_desc'){
            $sortBy = 'products.id';
            $sortOrder = 'desc';
        }
        if ($sortBy === ''){
            $sortBy = 'products.id';
        }

        $query->orderBy($sortBy, $sortOrder);

        $products = $query->select('products.*', DB::raw("DATE_FORMAT(products.created_at, '%d-%m-%Y %H:%i') as formatted_created_at"))
                          ->with([
                                  'categories',
                                  'tags',
                                  'latestPrice',
                                  'prices',
                                  'images',
                                  'featuredImage',
                                  'variants',
                                  'brand',
                                  'activePriceHistory'
                              ])->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $products->items(),
            'total'        => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
        ];
    }

    public function getById(int $id): Product
    {
        return $this->model->with(['brand', 'categories', 'tags', 'prices', 'images', 'variants', 'sizes'])->findOrFail($id);
    }

    public function getByCategoryId(int $categoryId): Collection
    {
        return $this->model::query()
                           ->whereHas('categories', function ($q) use ($categoryId) {
                               $q->where('id', $categoryId);
                           })
                           ->with(['latestPrice'])
                           ->get();
    }

    public function getByBrandId(int $brandId): Collection
    {
        return $this->model::query()
                           ->where('brand_id', $brandId)
                           ->with(['latestPrice'])
                           ->get();
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function store(array $data): Product
    {
        return $this->transaction(function() use ($data) {
            $product = $this->model::create($data);
            $this->setProduct($product);
            $product->categories()->sync($data['category_ids'] ?? []);
            $product->tags()->sync($data['tag_ids'] ?? []);

            // Ürün fiyatını ekle
            if (!empty($data['price'])) {
                $this->savePriceAndHistory($product, $data['price'], $data['price_discount'] ?? null, 'Ürün oluşturuldu.');
            }
            // Resimleri yükle
            if (!empty($data['images'])) {
                foreach ($data['images'] as $index => $uploadedFile) {
                    if ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {

                        $path = $uploadedFile->store('products', 'public');
                        \Log::info('Featured Image:', ['getClientOriginalName' => $uploadedFile->getClientOriginalName()]);

                        $product->images()->create([
                                                       'image_path'  => $path,
                                                       'is_featured' => $data['featured_image'] === $data['image_ids'][$index]
                                                   ]);
                    }
                }
            }

            // Ürün varyantlarını ekle
            if (!empty($data['variants'])) {
                $product->variants()->sync($data['variants']);
            }

            // Bedenleri senkronize et
            if (!empty($data['sizes'])) {
                $this->syncSizes($data['sizes']);
            }

            return $product;
        });
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function update(array $data): self
    {
        return $this->transaction(function() use ($data) {
            $this->model->update($data);
            $this->model->categories()->sync($data['category_ids'] ?? []);
            $this->model->tags()->sync($data['tag_ids'] ?? []);

            // Son eklenen fiyat kaydını al
            $lastPrice   = $this->model->prices()->latest('created_at')->first();
            $newPrice    = $data['price'] ?? null;
            $newDiscount = $data['price_discount'] ?? null;

            // Yeni fiyat, son kaydedilen fiyattan farklıysa yeni kayıt oluştur
            if (!empty($data['price']) && (
                    !$lastPrice ||
                    $lastPrice->price != $data['price'] ||
                    $lastPrice->price_discount != ($data['price_discount'] ?? null)
                )) {
                \Log::info('İndirimli Fİyat:  ' . $newDiscount . ' - ' . $newPrice);

                $this->savePriceAndHistory($this->model, $newPrice, $newDiscount, 'Ürün güncellendi ve fiyatı değiştirildi.');
            }

            // Silinecek görselleri belirle ve sil
            if (isset($data['existing_images'])) {
                $this->model->images()
                            ->whereNotIn('id', $data['existing_images'])
                            ->each(function ($image)
                            {
                                if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                                    Storage::disk('public')->delete($image->image_path);
                                }
                                $image->delete();
                            });
                //                            ->delete();
            }
            // Önce tüm görsellerin featured durumunu false yap
            $this->model->images()->update(['is_featured' => false]);

            // Mevcut görseller arasında featured olanı güncelle
            if (!empty($data['existing_images']) && $data['featured_image']) {
                $this->model->images()
                            ->whereIn('id', $data['existing_images'])
                            ->where('id', $data['featured_image'])
                            ->update(['is_featured' => true]);
            }

            // Resimleri yükle
            if (!empty($data['images'])) {
                foreach ($data['images'] as $index => $uploadedFile) {
                    if ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {

                        $path = $uploadedFile->store('products', 'public');
                        \Log::info('Featured Image:', ['getClientOriginalName' => $uploadedFile->getClientOriginalName()]);
                        $isFeatured = $data['featured_image'] === $data['image_ids'][$index];

                        $this->model->images()->create([
                                                           'image_path'  => $path,
                                                           'is_featured' => $isFeatured
                                                       ]);
                    }
                }
            }

            // Ürün varyantlarını güncelle
            if (!empty($data['variants'])) {
                $this->model->variants()->sync($data['variants']);
            }

            // update() içinde: “sizes” alanı gönderildiyse, boş da olsa sync et
            if (array_key_exists('sizes', $data)) { // alan var mı kontrolü; [] gönderildiyse de çalışır
                $this->syncSizes($data['sizes']);
            }

            return $this;
        });
    }
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function delete(): bool|null
    {
        return $this->transaction(function()
        {
            $this->model->categories()->detach();
            $this->model->tags()->detach();
            $this->model->prices()->delete();
            $this->model->discounts()->delete();
            // Görsellerin dosyalarını da sil
            foreach ($this->model->images as $image) {
                if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }
            $this->model->images()->delete();
            $this->model->variants()->detach();

            return $this->model->delete();
        });
    }

    /**
     * @throws Throwable
     */
    private function savePriceAndHistory(Product $product, float $price, ?float $priceDiscount = null, string $reason): void
    {
        $newPrice = $product->prices()->create([
                                                   'price'          => $price,
                                                   'price_discount' => $priceDiscount,
                                                   'updated_by'     => auth()->id()
                                               ]);

        app(ProductPriceHistoryService::class)->createHistory($product, $newPrice, $reason);
    }


    public function setProduct(Product $product): self
    {
        $this->model = $product;
        return $this;
    }

    public function changeStatus(?int $isActive = null): Product
    {

        $this->model->update([
                                 'is_active' => $isActive ?? !$this->model->is_active
                             ]); // Durumu tersine çevir

        return $this->model;
    }

    public function getLatest(int $limit = 8): Collection
    {
        $products =  $this->model
            ->with(['categories', 'brand', 'tags','latestPrice', 'featuredImage'])
            ->where('is_active', true)
            ->latest()
            ->limit($limit)
            ->get();

        return $this->enrichProductPrices($products);
    }
    public function enrichProductPrices(Collection|Product $products): Collection|Product
    {
        $enrich = function (Product $product) {
            if ($product->activePriceHistory) {
                $product->final_price = $product->activePriceHistory->price_discount ?? $product->activePriceHistory->price;
            } else {
                $product->final_price = null;
            }

            return $product;
        };

        if ($products instanceof Product) {
            return $enrich($products);
        }

        return $products->map(fn ($product) => $enrich($product));
    }

    public function getFrontendPaginatedProducts(int $limit = 12): LengthAwarePaginator
    {
        return $this->model::query()
                           ->select('id', 'name', 'slug', 'short_description', 'brand_id')
                           ->with([
                                      'categories:id,name,slug',
                                      'tags:id,name,slug',
                                      'brand:id,name,slug',
                                      'latestPrice:id,product_id,price',
                                      'images' => fn($q) => $q->select('id', 'product_id', 'image')->orderBy('order')->limit(1),
                                  ])
                           ->where('is_active', true)
                           ->orderByDesc('id')
                           ->paginate($limit);
    }

    /**
     * Gelen newSizes dizisini product_size_stocks ile eşitler:
     * - Yeni beden oluşturur
     * - Var olanı günceller ve restore eder
     * - Listede olmayanları soft-delete eder
     */
    protected function syncSizes(array $newSizes): void
    {
        // Mevcut kayıtları silinmiş + silinmemiş olarak çek
        $existingSizes = $this->model
            ->sizes()
            ->withTrashed()
            ->get();

        // Yeni gelen bedenler üzerinden döngü
        collect($newSizes)->each(function ($newSize) use ($existingSizes) {
            $sizeKey = trim($newSize['size']);
            $stock   = (int) $newSize['stock'];
            $alert   = (int) $newSize['stock_alert'];

            $existingSize = $existingSizes->first(function ($e) use ($sizeKey) {
                return $e->size === $sizeKey;
            });

            if ($existingSize) {
                // Güncelle ve gerekirse restore et
                $existingSize->update([
                                          'stock'       => $stock,
                                          'stock_alert' => $alert,
                                      ]);
                if ($existingSize->trashed()) {
                    $existingSize->restore();
                }
            } else {
                // Yeni kayıt
                $this->model->sizes()->create([
                                                  'size'        => $sizeKey,
                                                  'stock'       => $stock,
                                                  'stock_alert' => $alert,
                                              ]);
            }
        });

        // İstekte olmayan kayıtları soft-delete et
        $toDelete = $existingSizes->filter(function ($existingSize) use ($newSizes) {
            return ! collect($newSizes)->pluck('size')->contains($existingSize->size);
        });

        $toDelete->each->delete();
    }


}
