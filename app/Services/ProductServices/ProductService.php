<?php

namespace App\Services\ProductServices;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
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
        $minPrice         = $filter['min_price'] ?? null;
        $maxPrice         = $filter['max_price'] ?? null;
        $minPriceDiscount = $filter['min_price_discount'] ?? null;
        $maxPriceDiscount = $filter['max_price_discount'] ?? null;

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

            $query->orWhereHas('prices', function ($priceQuery) use ($search)
            {
                $priceQuery->where('price', 'like', "%{$search}%")
                           ->orWhere('price_discount', 'like', "%{$search}%");
            });
        }

        if (!empty($categories))
        {
            $query->whereHas('categories', function ($categoryQuery) use ($categories)
            {
                $categoryQuery->whereIn('category_id', $categories);
            });
        }
        if (!empty($brands))
        {
            $query->whereIn('brand_id', $brands);
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
            $query->whereHas('prices', function ($priceQuery) use ($minPrice)
            {
                $priceQuery->where('price', '>=', $minPrice);
            });
        }
        if (!empty($maxPrice))
        {
            $query->whereHas('prices', function ($priceQuery) use ($maxPrice)
            {
                $priceQuery->where('price', '<=', $maxPrice);
            });
        }
        if (!empty($minPriceDiscount))
        {
            $query->whereHas('prices', function ($priceQuery) use ($minPriceDiscount)
            {
                $priceQuery->where('price_discount', '>=', $minPriceDiscount);
            });
        }
        if (!empty($maxPriceDiscount))
        {
            $query->whereHas('prices', function ($priceQuery) use ($maxPriceDiscount)
            {
                $priceQuery->where('price_discount', '<=', $maxPriceDiscount);
            });
        }


        if ($sortBy === 'price'){
            $query->leftJoin('product_prices', function ($join) {
                $join->on('product_prices.product_id', '=', 'products.id')
                     ->whereRaw('product_prices.id = (SELECT MAX(id) FROM product_prices WHERE product_prices.product_id = products.id)');
            });
            $sortBy = 'product_prices.price';
        }
        $query->orderBy($sortBy, $sortOrder);
        \Log::info('$sortBy: ' . $sortBy . ' - sortorder: ' . $sortOrder);
        $products = $query->select('products.*', DB::raw("DATE_FORMAT(products.created_at, '%d-%m-%Y %H:%i') as formatted_created_at"))
                          ->with(['categories', 'tags', 'latestPrice','prices', 'discounts', 'images', 'variants', 'brand'])
                          ->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $products->items(),
            'total'        => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
        ];
    }

    public function getById(int $id): Product
    {
        return $this->model->with(['brand', 'categories', 'tags', 'prices', 'discounts', 'images', 'variants'])->findOrFail($id);
    }

    /**
     * @throws Exception
     */
    public function store(array $data): Product
    {
        DB::beginTransaction();
        try {
            $product = $this->model::create($data);
            $product->categories()->sync($data['category_ids'] ?? []);
            $product->tags()->sync($data['tag_ids'] ?? []);

            // Ürün fiyatını ekle
            if (!empty($data['price'])) {
                $product->prices()->create([
                                               'price'          => $data['price'],
                                               'price_discount' => $data['price_discount'] ?? null,
                                               'updated_by'     => auth()->id()
                                           ]);
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

            DB::commit();
            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function update(array $data): self
    {
        DB::beginTransaction();
        try {
            $this->model->update($data);
            $this->model->categories()->sync($data['category_ids'] ?? []);
            $this->model->tags()->sync($data['tag_ids'] ?? []);

            // Son eklenen fiyat kaydını al
            $lastPrice = $this->model->prices()->latest('created_at')->first();

            // Yeni fiyat, son kaydedilen fiyattan farklıysa yeni kayıt oluştur
            if (!empty($data['price']) && (
                    !$lastPrice ||
                    $lastPrice->price != $data['price'] ||
                    $lastPrice->price_discount != ($data['price_discount'] ?? null)
                )) {
                \Log::info('İndirimli Fİyat:  ' . $data['price_discount']);
                $this->model->prices()->create([
                                                   'price'          => $data['price'],
                                                   'price_discount' => $data['price_discount'] ?? null,
                                                   'updated_by'     => auth()->id()
                                               ]);
            }

            // Silinecek görselleri belirle ve sil
            if (isset($data['existing_images'])) {
                $this->model->images()
                            ->whereNotIn('id', $data['existing_images'])
                            ->each(function ($image)
                            {
                                if ($image->image_path && Storage::disk('public')->exists($image->image_path))
                                {
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

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete(): bool|null
    {
        DB::beginTransaction();
        try {
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
            $result = $this->model->delete();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        return $this->model
            ->with(['categories', 'brand', 'tags','latestPrice', 'featuredImage'])
            ->where('is_active', true)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
