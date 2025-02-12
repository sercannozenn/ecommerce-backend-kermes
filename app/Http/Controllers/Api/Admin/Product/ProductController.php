<?php

namespace App\Http\Controllers\Api\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductStoreRequest;
use App\Http\Requests\Admin\Product\ProductUpdateRequest;
use App\Models\Product;
use App\Services\CategoryServices\CategoryService;
use App\Services\ProductServices\ProductService;
use App\Services\TagServices\TagService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function create(TagService $tagService, CategoryService $categoryService)
    {
        try {
            $categories = $categoryService->getAll();
            $tags       = $tagService->getAll();
            $data       = [
                'categories' => $categories,
                'tags'       => $tags
            ];
            return $this->success($data);

        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Sayfa açılırken hata oluştu.']);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getPaginatedProducts(
            $request->input('page', 1),
            $request->input('limit', 10),
            $request->input('search', ''),
            $request->input('sortBy', 'created_at'),
            $request->input('sortOrder', 'desc')
        );
        return $this->success($products);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getById($id);
            return $this->success($product);
        } catch (Exception $e) {
            return $this->error(404, ['error' => 'Ürün bulunamadı.']);
        }
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->store($request->validated());
            return $this->success($product, 201);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ürün oluşturulurken bir hata oluştu.', 'message' => $e->getMessage()]);
        }
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        try {
            $this->productService->setProduct($product)->update($request->validated());
            return $this->success($product);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ürün güncellenirken bir hata oluştu.']);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->setProduct($product)->delete();
            return $this->success(null);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ürün silinirken bir hata oluştu.']);
        }
    }

    public function changeStatus(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getById($id);
            $product = $this->productService->setProduct($product)->changeStatus();
            return $this->success($product);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ürün durumu değiştirilemedi.']);
        }
    }

}
