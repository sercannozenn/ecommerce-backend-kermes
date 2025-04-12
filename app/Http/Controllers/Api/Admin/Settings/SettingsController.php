<?php

namespace App\Http\Controllers\Api\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\SettingStoreRequest;
use App\Http\Requests\Admin\Settings\SettingUpdateRequest;
use App\Models\Setting;
use App\Services\SettingsService\SettingsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settingsService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->settingsService->getPaginatedSettings(
                $request->query('page', 1),
                $request->query('limit', 10),
                $request->query('search', ''),
                $request->query('sort_by', 'id'),
                $request->query('sort_order', 'desc')
            );

            return $this->success($data);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ayar listesi getirilemedi.', 'message' => $e->getMessage()]);
        }
    }

    public function show(Setting $setting): JsonResponse
    {
        if (in_array($setting->key, ['logo', 'favicon']) && $setting->value) {
            $setting->value = asset('storage/' . $setting->value);
        }

        return $this->success($setting);
    }

    public function store(SettingStoreRequest $request): JsonResponse
    {
        try {
            $setting = $this->settingsService->store(
                $request->only('key', 'value'),
                $request->file('logo'),
                $request->file('favicon')
            );

            return $this->success($setting, 201);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ayar kaydedilemedi.', 'message' => $e->getMessage()]);
        }
    }

    public function update(Setting $setting, SettingUpdateRequest $request): JsonResponse
    {
        try {
            $updated = $this->settingsService->setSetting($setting)->update(
                $request->only('value'),
                $request->file('logo'),
                $request->file('favicon')
            );

            return $this->success($updated);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ayar güncellenemedi.', 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Setting $setting): JsonResponse
    {
        try {
            $this->settingsService->setSetting($setting)->delete();
            return $this->success(['message' => 'Ayar silindi.']);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Ayar silinemedi.', 'message' => $e->getMessage()]);
        }
    }
}
