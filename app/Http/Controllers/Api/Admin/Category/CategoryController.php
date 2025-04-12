<?php

namespace App\Http\Controllers\Api\Admin\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\CategoryStoreRequest;
use App\Http\Requests\Admin\Category\CategoryUpdateRequest;
use App\Models\Category;
use App\Services\CategoryServices\CategoryService;
use App\Helpers\ResponseFormatter;
use App\Services\TagServices\TagService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ResponseFormatter;

    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $page      = $request->input('page', 1);
            $limit     = $request->input('limit', 10);
            $search    = $request->input('search', '');
            $sortBy    = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $categories = $this->categoryService->getPaginatedTags($page, $limit, $search, $sortBy, $sortOrder);

            return $this->success($categories);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Kategoriler alınırken bir hata oluştu.']);
        }
    }

    public function create(TagService $tagService)
    {
        try {
            $categories = $this->categoryService->getAll();
            $tags       = $tagService->getAll();
            $data       = [
                'categories' => $categories,
                'tags'       => $tags
            ];
            return $this->success($data);

        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Kategoriler alınırken bir hata oluştu.']);
        }
    }

    public function store(CategoryStoreRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->store($request->validated());
            return $this->success($category, 201);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Kategori oluşturulurken bir hata oluştu.', 'hata' => $e->getMessage()]);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getById($id);
            return $this->success($category);
        } catch (Exception $e) {
            return $this->error(404, ['error' => 'Kategori bulunamadı.']);
        }
    }

    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        try {
            $this->categoryService->setCategory($category)->update($request->validated());
            return $this->success($category);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Kategori güncellenirken bir hata oluştu.']);
        }
    }

    public function changeStatus(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getById($id);
            $category = $this->categoryService->setCategory($category)->changeStatus();
            return $this->success($category);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Kategori durumu değiştirilemedi.']);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->setCategory($category)->delete();
            return $this->success(null);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Kategori silinirken bir hata oluştu.', 'message' => $e->getMessage()]);
        }
    }
}
