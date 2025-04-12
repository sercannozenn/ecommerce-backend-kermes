<?php

namespace App\Http\Controllers\Api\Admin\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Announcement\AnnouncementStoreRequest;
use App\Http\Requests\Admin\Announcement\AnnouncementUpdateRequest;
use App\Models\Announcement;
use App\Services\AnnouncementServices\AnnouncementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcementService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $announcements = $this->announcementService->getPaginatedAnnouncements(
            $request->query('page', 1),
            $request->query('limit', 10),
            $request->query('filter', []),
            $request->query('sort_by', 'created_at'),
            $request->query('sort_order', 'desc')
        );

        return $this->success($announcements);
    }
    public function store(AnnouncementStoreRequest $request): JsonResponse
    {
        try
        {
            $announcement = $this->announcementService->store($request->validated());
            return $this->success($announcement);
        }
        catch (Exception $e)
        {
            return $this->error(500, ['error' => 'Duyuru Etkinlik kaydedilemedi.', 'message' => $e->getMessage()]);
        }
    }
    public function show(Announcement $announcement): JsonResponse
    {
        return $this->success($announcement);
    }
    public function update(Announcement $announcement, AnnouncementUpdateRequest $request): JsonResponse
    {
        try
        {
            $announcement = $this->announcementService->setAnnouncement($announcement)->update($request->validated());
            return $this->success($announcement);
        }
        catch (Exception $e)
        {
            return $this->error(500, ['error' => 'Duyuru Etkinlik güncellenemedi.']);
        }
    }
    public function destroy(Announcement $announcement): JsonResponse
    {
        try {
            $this->announcementService->setAnnouncement($announcement)->delete();
            return $this->success(['message' => 'Duyuru Etkinlik silindi.']);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Duyuru Etkinlik silinemedi.']);
        }
    }
    public function changeStatus(Announcement $announcement): JsonResponse
    {
        try {
            $announcement = $this->announcementService->setAnnouncement($announcement)->changeStatus();
            return $this->success($announcement);
        } catch (Exception $e) {
            return $this->error(500, ['error' => 'Durum değiştirilemedi.']);
        }
    }

}
