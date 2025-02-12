<?php

namespace App\Services\ProductServices;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private Product $model)
    {
    }

    public function getAll(): Collection
    {
        return $this->model->with(['categories', 'tags', 'prices', 'discounts', 'images', 'variants'])->get();
    }

    public function getPaginatedProducts($page, $limit, $search, $sortBy, $sortOrder): array
    {
        $query = $this->model::query();

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('long_description', 'like', "%{$search}%");
        }


        if ($sortBy && in_array($sortBy, ['name', 'price', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->with(['categories', 'tags', 'prices', 'discounts', 'images', 'variants'])
                          ->paginate($limit, ['*'], 'page', $page);

        return $products->toArray();
    }

    public function getById(int $id): Product
    {
        return $this->model->with(['categories', 'tags', 'prices', 'discounts', 'images', 'variants'])->findOrFail($id);
    }

    /**
     * @throws Exception
     */
    public function store(array $data): Product
    {
        \Log::info('Gelen resim verisi:', ['images' => $data['images']]);
        \Log::info('Featured Image:', ['featured' => $data['featured_image']]);
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
                            'image_path' => $path,
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

                $this->model->prices()->create([
                                                   'price'          => $data['price'],
                                                   'price_discount' => $data['price_discount'] ?? null,
                                                   'updated_by'     => auth()->id()
                                               ]);
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
}
