<?php

namespace App\Http\Controllers\Api\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductDiscountRequest;
use App\Http\Requests\Admin\Product\ProductDiscountUpdateRequest;
use App\Models\ProductDiscount;
use App\Services\ProductServices\DiscountService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductDiscountController extends Controller
{
    public function __construct(protected DiscountService $discountService)
    {
    }
    public function searchTargets(Request $request)
    {
        $request->validate([
                               'type' => 'required|in:product,category,brand,tag,user',
                               'q'    => 'nullable|string|max:255',
                           ]);

        $type = $request->get('type');
        $query = $request->get('q');

        return response()->json($this->discountService->searchTargets($type, $query));
    }

    public function index(Request $request)
    {
        $page       = (int) $request->input('page', 1);
        $limit      = (int) $request->input('limit', 10);
        $sortBy     = $request->input('sort_by', 'id');
        $sortOrder  = $request->input('sort_order', 'asc');
        $filter     = $request->input('filter', []);

        return $this->success(
            $this->discountService->getPaginatedDiscounts($page, $limit, $filter, $sortBy, $sortOrder)
        );
    }

    /**
     * @throws \Throwable
     */
    public function store(ProductDiscountRequest $request): JsonResponse
    {
        $discount = $this->discountService->store($request->validated());

        return $this->success($discount);
    }

    public function update(ProductDiscountUpdateRequest $request, ProductDiscount $productDiscount): JsonResponse
    {
        $discount = $this->discountService->update($productDiscount->id, $request->validated());

        return response()->json([
                                    'success' => true,
                                    'data' => $discount,
                                ]);
    }

    public function show(ProductDiscount $productDiscount)
    {
        $productDiscount->load('targets');

        $modelMap = [
            'product'  => \App\Models\Product::class,
            'category' => \App\Models\Category::class,
            'brand'    => \App\Models\Brand::class,
            'tag'      => \App\Models\Tag::class,
            'user'     => \App\Models\User::class,
        ];

        $modelClass = $modelMap[$productDiscount->target_type] ?? null;

        $targets = [];
        if ($modelClass) {
            $targetIds = $productDiscount->targets->pluck('target_id');
            $targets = $modelClass::whereIn('id', $targetIds)
                                  ->get()
                                  ->map(fn($item) => ['value' => $item->id, 'label' => $item->name])
                                  ->toArray();
        }
        unset($productDiscount->targets); // ilişkiyi sıfırla
        $productDiscount->targets = $targets;
        return $this->success($productDiscount);
    }

    /**
     * @throws \Throwable
     */
    public function changeStatus(ProductDiscount $product_discount) {
        $updated  = $this->discountService
            ->setDiscount($product_discount)
            ->changeStatus();

        return $this->success($updated);
    }

    public function getAffectedProducts(ProductDiscount $productDiscount)
    {
        $products = $this->discountService->getAffectedProducts($productDiscount);

        return $this->success($products);
    }

}
