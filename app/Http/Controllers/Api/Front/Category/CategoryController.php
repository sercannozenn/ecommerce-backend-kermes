<?php

namespace App\Http\Controllers\Api\Front\Category;

use App\Http\Controllers\Controller;
use App\Services\CategoryServices\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function subcategories(string $slug): JsonResponse
    {
        $subs = $this->categoryService->getSubcategoriesActiveBySlug($slug);
        return $this->success($subs);
    }

}
