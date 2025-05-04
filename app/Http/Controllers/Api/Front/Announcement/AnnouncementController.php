<?php

namespace App\Http\Controllers\Api\Front\Announcement;

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

    public function getActiveAnnouncementsAndEvents(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 2);
        $offset = $request->get('offset', 0);
        $is_Active = $request->get('is_active', 1);

        $items = $this->announcementService->getActiveAnnouncementsAndEvents($limit, $offset, $is_Active);
        return $this->success($items);
    }

    public function detail(int $id)
    {
        $announcement = $this->announcementService->getById($id);

//        $announcement->image_url = $announcement->image ? asset('storage/' . $announcement->image) : null;
        $announcement->formatted_date = $announcement->date ? $announcement->date->format('d-m-Y') : null;

        return $this->success($announcement);
    }


}
