<?php

namespace App\Services\BrandServices;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BrandService
{
    public function __construct(private Brand $model ) {}
    public function getAll(): Collection
    {
        return $this->model->get();
    }
    public function getById(int $id): Brand
    {
        return $this->model::query()->findOrFail($id);
    }

    public function store(array $data): Brand
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
    public function setBrand(Brand $Brand): self
    {
        $this->model = $Brand;
        return $this;
    }
    public function getPaginatedBrands($page, $limit, $search, $sortBy, $sortOrder): array
    {
        $query = $this->model::query();

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        $query->orderBy($sortBy, $sortOrder);

        $brands = $query->select('*',
                                 DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as formatted_created_at")
        )->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $brands->items(),
            'total'        => $brands->total(),
            'current_page' => $brands->currentPage(),
            'last_page'    => $brands->lastPage(),
        ];
    }
    public function changeStatus(?int $isActive = null): Brand
    {
        $this->model->update([
                                 'is_active' => $isActive ?? !$this->model->is_active
                             ]); // Durumu tersine çevir

        return $this->model;
    }

    public function getActiveBrands(): Collection
    {
        return $this->model::query()
                           ->select('id', 'name', 'slug')
                           ->where('is_active', true)
                           ->orderBy('name')
                           ->get();
    }
}
