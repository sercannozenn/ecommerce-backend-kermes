<?php

namespace App\Http\Controllers\Api\Front\Product;

use App\Http\Controllers\Controller;
use App\Services\BrandServices\BrandService;
use App\Services\CategoryServices\CategoryService;
use App\Services\ProductServices\ProductService;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function latest(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        $products = $this->productService->getLatest($limit);
        return $this->success($products);
    }

    /**
     * @throws BindingResolutionException
     */
    public function init(Request $request)
    {
        $filters = $request->all();
        $limit   = $request->get('limit', 12);

        $categoryService = app()->make(CategoryService::class);
        $brandService    = app()->make(BrandService::class);

        return response()->json([
                                    'success' => true,
                                    'data'    => [
                                        'products'   => $this->productService->getFrontendPaginatedProducts($filters, $limit),
                                        'categories' => $categoryService->getActiveCategories(),
                                        'brands'     => $brandService->getActiveBrands(),
                                    ]
                                ]);
    }
}
