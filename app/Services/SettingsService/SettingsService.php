<?php

namespace App\Services\SettingsService;

use App\Models\Setting;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    public function __construct(protected Setting $model)
    {
    }
    public function setSetting(Setting $setting): self
    {
        $this->model = $setting;
        return $this;
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->latest()->get();
    }

    public function getPaginatedSettings(int $page = 1, int $limit = 10, string $search = '', string $sortBy = 'id', string $sortOrder = 'desc'): array
    {
        $query = $this->model::query();

        if (!empty($search)) {
            $query->where('key', 'like', "%$search%")
                  ->orWhere('value', 'like', "%$search%");
        }

        if ($sortBy === 'formatted_created_at')
        {
            $sortBy = 'created_at';
        }

        $settings = $query->select('*',
                                   DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as formatted_created_at"))
                          ->orderBy($sortBy, $sortOrder)
                          ->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $settings->items(),
            'total'        => $settings->total(),
            'current_page' => $settings->currentPage(),
            'last_page'    => $settings->lastPage(),
        ];
    }

    public function getById(int $id): Setting
    {
        return $this->model->findOrFail($id);
    }

    /**
     * @throws Exception
     */
    public function store(array $data, ?UploadedFile $logo = null, ?UploadedFile $favicon = null): Setting
    {
        DB::beginTransaction();
        try {
            if ($logo) {
                $data['key'] = 'logo';
                $data['value'] = $logo->store('settings/logo', 'public');
            }

            if ($favicon) {
                $data['key'] = 'favicon';
                $data['value'] = $favicon->store('settings/favicon', 'public');
            }

            $setting = $this->model->create($data);

            DB::commit();
            return $setting;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function update(array $data, ?UploadedFile $logo = null, ?UploadedFile $favicon = null): Setting
    {
        DB::beginTransaction();
        try {
            if ($this->model->key === 'logo' && $logo) {
                if (Storage::disk('public')->exists($this->model->value)) {
                    Storage::disk('public')->delete($this->model->value);
                }
                $data['value'] = $logo->store('settings/logo', 'public');
            }

            if ($this->model->key === 'favicon' && $favicon) {
                if (Storage::disk('public')->exists($this->model->value)) {
                    Storage::disk('public')->delete($this->model->value);
                }
                $data['value'] = $favicon->store('settings/favicon', 'public');
            }

            $this->model->update($data);

            DB::commit();
            return $this->model;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function delete(): ?bool
    {
        DB::beginTransaction();
        try {
            $result = $this->model->delete();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
