<?php

namespace App\Http\Controllers\Api\Front\Settings;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Services\BrandServices\BrandService;
use App\Services\CategoryServices\CategoryService;
use App\Services\ProductServices\ProductService;
use App\Services\SettingsService\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settingsService)
    {
    }

    public function index()
    {
        $settings = $this->settingsService
            ->getAll()
            ->mapWithKeys(function ($setting) {
                return [
                    $setting->key => $setting->value_url ?? $setting->value,
                ];
            })
            ->toArray();
        return $this->success($settings);
    }
}
