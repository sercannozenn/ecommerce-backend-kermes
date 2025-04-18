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

        $items = $this->announcementService->getActiveAnnouncementsAndEvents($limit, $offset);
        return $this->success($items);
    }

}
