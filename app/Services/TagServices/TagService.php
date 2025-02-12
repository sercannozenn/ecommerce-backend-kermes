<?php

namespace App\Services\TagServices;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    public function __construct(private Tag $model ) {}

    public function getAll(): Collection
    {
        return $this->model->with(['categories'])->get();
    }

    public function getById(int $id): Tag
    {
        return $this->model::query()->findOrFail($id);
    }

    public function store(array $data): Tag
    {
        return $this->model::create($data);
    }

    public function update(array $data): self
    {
        $this->model->update($data);

        return $this;
    }

    public function delete(): bool|null
    {
        return $this->model->delete();
    }

    public function setTag(Tag $tag): self
    {
        $this->model = $tag;
        return $this;
    }

    public function getPaginatedTags($page, $limit, $search, $sortBy, $sortOrder): array
    {
        $query = $this->model::query();

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        $query->orderBy($sortBy, $sortOrder);

        $tags = $query->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $tags->items(),
            'total'        => $tags->total(),
            'current_page' => $tags->currentPage(),
            'last_page'    => $tags->lastPage(),
        ];
    }
}
