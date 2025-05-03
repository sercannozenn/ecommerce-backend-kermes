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

    public function index(Request $request)
    {
        $products = $this->productService->getPaginatedProducts(
            page: $request->query('page', 1),
            limit: $request->query('limit', 1),
            filter: $request->query('filter', []),
            sortBy: $request->query('sortBy', 'id'),
            sortOrder: $request->query('sortOrder', 'desc')
        );

        // Fiyatı zenginleştir
        $data = $this->productService->enrichProductPrices(collect($products['data']));

        return $this->success([
                                  'data'         => $data,
                                  'meta' => [
                                      'current_page' => $products['current_page'],
                                      'last_page'    => $products['last_page'],
                                      'total'        => $products['total'],
                                      'per_page'     => $request->input('limit', 1),
                                  ],
                              ]);
    }
}
