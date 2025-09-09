<?php

namespace App\Services;

use App\Models\Discount;
use App\Http\Resources\DiscountResource;

class DiscountService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new DiscountResource(new Discount), new Discount());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allDiscounts = $this->getTotalCount();
        $trashedDiscounts = $this->getTrashedCount();

        return DiscountResource::collection(Discount::query()
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                return $query->where('name', 'LIKE', '%' . request('search') . '%')
                             ->orWhere('code', 'LIKE', '%' . request('search') . '%');
            })
            ->when(request('active'), function ($query) {
                $active = request('active');
                if ($active === 'Active') {
                    $query->where('active', 1);
                } elseif ($active === 'Inactive') {
                    $query->where('active', 0);
                }
            })
            ->when(request('type'), function ($query) {
                $query->where('type', request('type'));
            })
            ->when(request('order'), function ($query) {
                return $query->orderBy(request('order'), request('sort'));
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allDiscounts, 'trashed' => $trashedDiscounts]]);
    }

    /**
     * Get active and valid discounts for dropdown
     */
    public function getActiveDiscounts()
    {
        return Discount::active()
            ->valid()
            ->select('id', 'name', 'code', 'amount', 'type')
            ->orderBy('name')
            ->get();
    }

    /**
     * Validate discount code
     */
    public function validateDiscountCode($code)
    {
        $discount = Discount::where('code', $code)->first();
        
        if (!$discount) {
            return ['valid' => false, 'message' => 'Discount code not found'];
        }

        if (!$discount->isValid()) {
            return ['valid' => false, 'message' => 'Discount code is not valid or has expired'];
        }

        return ['valid' => true, 'discount' => $discount];
    }
}
