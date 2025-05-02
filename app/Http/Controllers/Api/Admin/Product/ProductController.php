<?php

namespace App\Http\Controllers\Api\Admin\Product;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductStoreRequest;
use App\Http\Requests\Admin\Product\ProductUpdateRequest;

use App\Models\Product;
use App\Services\BrandServices\BrandService;
use App\Services\CategoryServices\CategoryService;
use App\Services\ProductServices\ProductPriceHistoryService;
use App\Services\ProductServices\ProductService;
use App\Services\TagServices\TagService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function create(TagService $tagService,
                           CategoryService $categoryService,
                           BrandService $brandService)
    {
        try {
            $categories = $categoryService->getAll();
            $tags       = $tagService->getAll();
            $brands     = $brandService->getAll();
            $data       = [
                'categories' => $categories,
                'tags'       => $tags,
                'brands'     => $brands,
                'genders'    => Gender::options(),
            ];
            return $this->success($data);

        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Sayfa açılırken hata oluştu.']);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getPaginatedProducts(
            $request->query('page', 1),
            $request->query('limit', 10),
            $request->query('filter', []),
            $request->query('sort_by', 'created_at'),
            $request->query('sort_order', 'desc')
        );
        return $this->success($products);
    }

    public function getGenders()
    {
        return response()->json(array_column(Gender::cases(), 'value'));
    }
    public function getFiltersData(CategoryService $categoryService, TagService $tagService, BrandService $brandService): JsonResponse
    {
        $data = [
            'categories' => $categoryService->getAll(),
            'tags'       => $tagService->getAll(),
            'brands'     => $brandService->getAll(),
        ];
        return $this->success($data);
    }

    public function show(int $id, CategoryService $categoryService, TagService $tagService, BrandService $brandService): JsonResponse
    {
        try {
            $categories = $categoryService->getAll();
            $tags       = $tagService->getAll();
            $brands     = $brandService->getAll();
            $product    = $this->productService->getById($id);
            $data       = [
                'categories' => $categories,
                'tags'       => $tags,
                'brands'     => $brands,
                'product'    => $product,
                'genders'    => Gender::options(),
            ];
            return $this->success($data);
        } catch (Exception $e) {
            return $this->error(404, ['error' => 'Ürün bulunamadı.', 'message' => $e->getMessage()]);
        }
    }

    /**
     * @throws Throwable
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
//        try {
            $product = $this->productService->store($request->validated());
            return $this->success($product, 201);
//        } catch (Exception $e) {
//            return $this->error(500, ['error' => 'Ürün oluşturulurken bir hata oluştu.', 'message' => $e->getMessage()]);
//        }
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        try {
            $this->productService->setProduct($product)->update($request->validated());
            return $this->success($product);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ürün güncellenirken bir hata oluştu.', 'message' => $e->getMessage()]);
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

    public function priceHistory(Product $product, ProductPriceHistoryService $productPriceHistoryService)
    {
        $history = $productPriceHistoryService->getHistory($product);
        return $this->success($history);
    }

}
