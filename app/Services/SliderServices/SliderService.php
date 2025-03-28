<?php

namespace App\Services\SliderServices;

use App\Models\Slider;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SliderService
{
    public function __construct(private Slider $model)
    {
    }
    public function getAll()
    {
        return $this->model->orderBy('id', 'desc')->get();
    }

    public function getPaginatedSliders(int $page = 1, int $limit = 10, array $filter = [], string $sortBy = 'id', string $sortOrder = 'desc'): array
    {
        $query = $this->model::query();

        if (!empty($filter['search'])) {
            $search = $filter['search'];
            $query->where(function ($q) use ($search) {
                $q->where('row_1_text', 'like', "%$search%")
                  ->orWhere('row_1_color', 'like', "%$search%")
                  ->orWhere('row_1_css', 'like', "%$search%")
                  ->orWhere('row_2_text', 'like', "%$search%")
                  ->orWhere('row_2_color', 'like', "%$search%")
                  ->orWhere('row_2_css', 'like', "%$search%")
                  ->orWhere('button_text', 'like', "%$search%")
                  ->orWhere('button_url', 'like', "%$search%")
                  ->orWhere('button_target', 'like', "%$search%")
                  ->orWhere('button_color', 'like', "%$search%")
                  ->orWhere('button_css', 'like', "%$search%")
                  ->orWhere('is_active', (int)$search);
            });
        }
        $sliders = $query->orderBy($sortBy, $sortOrder)->paginate($limit, ['*'], 'page', $page);

        return [
            'data'         => $sliders->items(),
            'total'        => $sliders->total(),
            'current_page' => $sliders->currentPage(),
            'last_page'    => $sliders->lastPage(),
        ];
    }
    public function setSlider(Slider $slider): self
    {
        $this->model = $slider;
        return $this;
    }
    /**
     * @throws Exception
     */
    public function store(array $data): Slider
    {
        DB::beginTransaction();
        try {
            if (isset($data['path']) && $data['path'] instanceof \Illuminate\Http\UploadedFile) {
                $data['path'] = $data['path']->store('sliders', 'public');
            }
            $slider = $this->model->create($data);
            DB::commit();
            return $slider;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    /**
     * @throws Exception
     */
    public function update(array $data): Slider
    {
        DB::beginTransaction();
        try
        {
            if (isset($data['path']) && $data['path'] instanceof \Illuminate\Http\UploadedFile)
            {
                if ($this->model->path && Storage::disk('public')->exists($this->model->path))
                {
                    Storage::disk('public')->delete($this->model->path);
                }
                $data['path'] = $data['path']->store('sliders', 'public');
            }
            else
            {
                // Yeni görsel yoksa path alanını unset et ki eski path overwrite edilmesin
                unset($data['path']);
            }
            $this->model->update($data);
            DB::commit();
            return $this->model;
        }
        catch (Exception $e)
        {
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
        try
        {
            if ($this->model->path && Storage::disk('public')->exists($this->model->path))
            {
                Storage::disk('public')->delete($this->model->path);
            }
            $result = $this->model->delete();
            DB::commit();
            return $result;
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
        }
    }
    public function changeStatus(): Slider
    {
        $this->model->update([
                                 'is_active' => !$this->model->is_active
                             ]);

        return $this->model;
    }

}
