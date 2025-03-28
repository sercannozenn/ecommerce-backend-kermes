<?php

namespace App\Http\Controllers\Api\Admin\Slider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Slider\SliderStoreRequest;
use App\Http\Requests\Admin\Slider\SliderUpdateRequest;
use App\Models\Slider;
use App\Services\SliderServices\SliderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function __construct(private SliderService $sliderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $sliders = $this->sliderService->getPaginatedSliders(
            $request->query('page', 1),
            $request->query('limit', 10),
            $request->query('filter', []),
            $request->query('sort_by', 'created_at'),
            $request->query('sort_order', 'desc')
        );

        return $this->success($sliders);
    }
    public function store(SliderStoreRequest $request): JsonResponse
    {
        try {
            $slider = $this->sliderService->store($request->validated());
            return $this->success($slider);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Slider kaydedilemedi.']);
        }
    }
    public function show(Slider $slider): JsonResponse
    {
        return $this->success($slider);
    }
    public function update(Slider $slider, SliderUpdateRequest $request): JsonResponse
    {
        try {
            $slider = $this->sliderService->setSlider($slider)->update($request->validated());
            return $this->success($slider);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Slider güncellenemedi.']);
        }
    }
    public function destroy(Slider $slider): JsonResponse
    {
        try {
            $this->sliderService->setSlider($slider)->delete();
            return $this->success(['message' => 'Slider silindi.']);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Slider silinemedi.']);
        }
    }
    public function changeStatus(Slider $slider): JsonResponse
    {
        try {
            $slider = $this->sliderService->setSlider($slider)->changeStatus();
            return $this->success($slider);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Durum değiştirilemedi.']);
        }
    }
}
