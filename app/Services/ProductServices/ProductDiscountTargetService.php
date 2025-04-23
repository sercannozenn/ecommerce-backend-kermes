<?php

namespace App\Services\ProductServices;

use App\Models\ProductDiscount;
use App\Models\ProductDiscountTarget;


class ProductDiscountTargetService
{
    public function __construct(protected ProductDiscountTarget $model)
    {
    }

    public function createTargets(int $discountId, string $targetType, array $targetIds): void
    {
        $data = collect($targetIds)->map(fn($id) => [
            'product_discount_id' => $discountId,
            'target_type'         => $targetType,
            'target_id'           => $id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ])->toArray();

        $this->model::insert($data);
    }

    public function deleteByDiscountId(int $discountId): bool
    {
        return $this->model::query()->where('product_discount_id', $discountId)->delete();
    }

    public function syncTargets(ProductDiscount $discount, array $targetIds): void
    {
        $this->deleteByDiscountId($discount->id);
        $this->createTargets($discount->id, $discount->target_type, $targetIds);
    }

    public function getQueryForConflictCheck(array $targetIds, string $targetType, string $start, string $end, ?int $excludeId = null)
    {
        return $this->model::query()
                           ->whereIn('target_id', $targetIds)
                           ->where('target_type', $targetType)
                           ->whereHas('discount', function ($query) use ($start, $end, $excludeId)
                           {
                               $query->where(function ($q) use ($start, $end)
                               {
                                   $q->whereBetween('discount_start', [$start, $end])
                                     ->orWhereBetween('discount_end', [$start, $end])
                                     ->orWhere(function ($q2) use ($start, $end)
                                     {
                                         $q2->where('discount_start', '<=', $start)
                                            ->where('discount_end', '>=', $end);
                                     });
                               });

                               if ($excludeId) {
                                   $query->where('id', '!=', $excludeId);
                               }
                           });
    }

}
