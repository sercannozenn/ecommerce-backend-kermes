<?php

namespace App\Services\CategoryServices;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(private Category $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['parent', 'tags'])->get();
    }
    public function getPaginatedTags($page, $limit, $search, $sortBy, $sortOrder): array
    {
        $query = $this->model::query();

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhereHas('parent', function ($q) use ($search){
                      $q->where('name', 'like', "%{$search}%");
                  });
        }
        $query->leftJoin('categories as parent_categories', 'categories.parent_category_id', '=', 'parent_categories.id');

        if ($sortBy === 'formatted_created_at')
        {
            $sortBy = 'categories.created_at';
        }

        if ($sortBy === 'parent_name')
        {
            // boş null olan kayıtlar da gelsin.
            $query->orderByRaw("COALESCE(parent_categories.name, 'ZZZ') $sortOrder");
        }
        else
        {
            $query->orderBy($sortBy, $sortOrder);
        }

        $categories = $query->select(
            'categories.*',
            'parent_categories.name as parent_name',
            DB::raw("DATE_FORMAT(categories.created_at, '%d-%m-%Y') as formatted_created_at")

        )->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $categories->items(),
            'total'        => $categories->total(),
            'current_page' => $categories->currentPage(),
            'last_page'    => $categories->lastPage(),
        ];
    }


    public function getById(int $id): Category
    {
        return $this->model->with(['children', 'tags'])->findOrFail($id);
    }

    public function store(array $data): Category|array
    {
        $category = $this->model::create($data);
        $tagIds = collect($data['tags'] ?? [])->pluck('value')->toArray();
        $tags = Tag::query()->whereIn('id', $tagIds)->get();
        $category->tags()->sync($tags);

        return $category;
    }

    public function update(array $data): self
    {
        $this->model->update($data);
        $tags = Tag::whereIn('id', $data['tags'] ?? [])->get();
        $this->model->tags()->sync($tags);

        return $this;
    }

    public function delete(): void
    {
        $this->model->tags()->detach();
        $this->model->delete();
    }

    public function setCategory(Category $category): self
    {
        $this->model = $category;
        return $this;
    }

    public function changeStatus(?int $isActive = null): Category
    {

        $this->model->update([
            'is_active' => $isActive ?? !$this->model->is_active
                             ]); // Durumu tersine çevir

        return $this->model;
    }
}
