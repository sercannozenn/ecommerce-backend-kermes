<?php

namespace App\Http\Controllers\Api\Admin\Brand;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\BrandStoreRequest;
use App\Http\Requests\Admin\Brand\BrandUpdateRequest;
use App\Services\BrandServices\BrandService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ResponseFormatter;

    public function __construct(protected BrandService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $page      = $request->input('page', 1);
            $limit     = $request->input('limit', 10);
            $search    = $request->input('search', '');
            $sortBy    = $request->input('sort_by', 'id');
            $sortOrder = $request->input('sort_order', 'asc');

            $brands = $this->service->getPaginatedBrands($page, $limit, $search, $sortBy, $sortOrder);
            return $this->success($brands);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Markalar alınırken bir hata oluştu.']);
        }
    }

    public function store(BrandStoreRequest $request): JsonResponse
    {
        try {
            $brand = $this->service->store($request->validated());
            return $this->success($brand, 201);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Marka oluşturulurken bir hata oluştu.']);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $brand = $this->service->getById($id);
            return $this->success($brand);
        } catch (Exception $e) {
            return $this->error(404, ['error' => 'Marka bulunamadı.']);
        }
    }

    public function update(BrandUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $brand = $this->service->getById($id);
            $this->service->setBrand($brand)->update($request->validated());
            return $this->success($brand);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Marka güncellenirken bir hata oluştu.']);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $brand = $this->service->getById($id);
            $this->service->setBrand($brand)->delete();
            return $this->success(null);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Marka silinirken bir hata oluştu.']);
        }
    }

    public function changeStatus(int $id): JsonResponse
    {
        try {
            $brand = $this->service->getById($id);
            $brand = $this->service->setBrand($brand)->changeStatus();
            return $this->success($brand);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Marka durumu değiştirilemedi.']);
        }
    }

}
