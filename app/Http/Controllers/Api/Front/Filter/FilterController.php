<?php

namespace App\Http\Controllers\Api\Front\Filter;

use App\Http\Controllers\Controller;
use App\Services\BrandServices\BrandService;
use App\Services\CategoryServices\CategoryService;
use App\Services\ProductServices\ProductService;
use Illuminate\Http\JsonResponse;

class FilterController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private BrandService    $brandService,
        private ProductService  $productService,
    ) {}

    public function index(): JsonResponse
    {
        // 1) Filtre tipi (genders)
        $genders    = $this->categoryService->getGenders();

        // 2) Tüm aktif alt kategoriler
        $categories = $this->categoryService->getActiveCategories();

        // 3) Aktif markalar
        $brands     = $this->brandService->getActiveBrands();

        return $this->success(compact('genders','categories','brands'));
    }
}
