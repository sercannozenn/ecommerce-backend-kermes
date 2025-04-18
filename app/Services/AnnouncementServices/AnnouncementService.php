<?php

namespace App\Services\AnnouncementServices;

use App\Models\Announcement;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementService
{
    public function __construct(protected Announcement $model)
    {
    }
    public function getAll(): Collection
    {
        return $this->model->latest()->get();
    }
    public function getPaginatedAnnouncements(int $page = 1, int $limit = 10, array $filter = [], string $sortBy = 'id', string $sortOrder = 'desc'): array
    {
        $query = $this->model::query();

        if (!empty($filters['search'])) {
            $search = $filter['search'];
            $query->where(function ($q) use ($search)
            {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('type', 'like', "%$search%")
                  ->orWhere('short_description', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }

        if (isset($filter['is_active'])) {
            $query->where('is_active', $filter['is_active']);
        }

        if (!empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }

        if (!empty($filter['date_from'])) {
            $query->whereDate('date', '>=', $filter['date_from']);
        }

        if (!empty($filter['date_to'])) {
            $query->whereDate('date', '<=', $filter['date_to']);
        }

        if ($sortBy === 'formatted_created_at')
        {
            $sortBy = 'created_at';
        }

        if ($sortBy === 'formatted_date')
        {
            $sortBy = 'date';
        }

        $announcements = $query
            ->select('*',
                     DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as formatted_created_at"),
                     DB::raw("DATE_FORMAT(date, '%d-%m-%Y') as formatted_date"))
            ->orderBy($sortBy, $sortOrder)->paginate($limit, ['*'], 'page', $page);
        return [
            'data'         => $announcements->items(),
            'total'        => $announcements->total(),
            'current_page' => $announcements->currentPage(),
            'last_page'    => $announcements->lastPage(),
        ];
    }
    public function getById(int $id): Announcement
    {
        return $this->model->findOrFail($id);
    }
    public function setAnnouncement(Announcement $announcement)
    {
        $this->model = $announcement;
        return $this;
    }
    /**
     * @throws Exception
     */
    public function store(array $data): Announcement
    {
        DB::beginTransaction();
        try {
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $data['image']->store('announcements', 'public');
            }
            $announcement = $this->model->create($data);
            DB::commit();
            return $announcement;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    /**
     * @throws Exception
     */
    public function update(array $data): Announcement
    {
        DB::beginTransaction();
        try
        {
            if (isset($data['image']) && $data['image'] instanceof UploadedFile)
            {
                if ($this->model->image && Storage::disk('public')->exists($this->model->image))
                {
                    Storage::disk('public')->delete($this->model->image);
                }
                $data['image'] = $data['image']->store('announcements', 'public');
            }
            else
            {
                // Yeni görsel gönderilmemişse:
                // Eğer eski görsel varsa onu sil ve image=null olarak güncelle
                if ($this->model->image && Storage::disk('public')->exists($this->model->image)) {
                    Storage::disk('public')->delete($this->model->image);
                }
                $data['image'] = null; // Bu satır kritik: null gönder ki veritabanında da silinsin
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
            if ($this->model->image && Storage::disk('public')->exists($this->model->image))
            {
                Storage::disk('public')->delete($this->model->image);
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
    public function changeStatus(): Announcement
    {
        $this->model->update([
                                 'is_active' => !$this->model->is_active
                             ]);

        return $this->model;
    }

    public function getActiveAnnouncementsAndEvents(int $limit = 2, int $offset = 0): Collection
    {
        return $this->model::query()
                           ->select('*',
                                    DB::raw("DATE_FORMAT(date, '%d-%m-%Y') as formatted_date")
                           )
                           ->where('is_active', true)
                           ->whereDate('date', '>=', now())
                           ->orderBy('date', 'DESC')
                           ->offset($offset)
                           ->limit($limit)
                           ->get();
    }



}
