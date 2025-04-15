<?php

namespace App\Http\Controllers\Api\Front\Slider;

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

    public function index(): JsonResponse
    {
        $sliders = $this->sliderService->getPaginatedSliders(
            limit:20, filter: ['is_active' => 1]
        )['data'];

        return $this->success($sliders);
    }
}
