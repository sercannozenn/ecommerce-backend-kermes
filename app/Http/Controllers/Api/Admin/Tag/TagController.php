<?php

namespace App\Http\Controllers\Api\Admin\Tag;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\BrandStoreRequest;
use App\Http\Requests\Admin\Brand\BrandUpdateRequest;
use App\Services\TagServices\TagService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    use ResponseFormatter;

    public function __construct(protected TagService $service)
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

            $tags = $this->service->getPaginatedTags($page, $limit, $search, $sortBy, $sortOrder);
            return $this->success($tags);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Etiketler alınırken bir hata oluştu.']);
        }
    }

    public function store(BrandStoreRequest $request): JsonResponse
    {
        try {
            $tag = $this->service->store($request->validated());
            return $this->success($tag, 201);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Etiket oluşturulurken bir hata oluştu.']);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $tag = $this->service->getById($id);
            return $this->success($tag);
        } catch (Exception $e) {
            return $this->error(404, ['error' => 'Etiket bulunamadı.']);
        }
    }

    public function update(BrandUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $tag = $this->service->getById($id);
            $this->service->setTag($tag)->update($request->validated());
            return $this->success($tag);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Etiket güncellenirken bir hata oluştu.']);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $tag = $this->service->getById($id);
            $this->service->setTag($tag)->delete();
            return $this->success(null);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Etiket silinirken bir hata oluştu.']);
        }
    }
}
