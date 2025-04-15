<?php

namespace App\Http\Controllers\Api\Front\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductStoreRequest;
use App\Http\Requests\Admin\Product\ProductUpdateRequest;

use App\Models\Product;
use App\Services\BrandServices\BrandService;
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

    public function latest(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        $products = $this->productService->getLatest($limit);
        return $this->success($products);
    }
}
