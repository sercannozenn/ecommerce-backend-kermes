<?php

namespace App\Http\Controllers\Api\Front\Brand;

use App\Http\Controllers\Controller;
use App\Services\BrandServices\BrandService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(protected BrandService $brandService)
    {
    }

    public function index(): JsonResponse
    {
        $brands = $this->brandService->getActiveBrands();
        return $this->success($brands);
    }
}
