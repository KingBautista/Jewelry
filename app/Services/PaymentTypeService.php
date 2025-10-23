<?php

namespace App\Services;

use App\Models\PaymentType;
use App\Http\Resources\PaymentTypeResource;

class PaymentTypeService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new PaymentTypeResource(new PaymentType), new PaymentType());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allPaymentTypes = $this->getTotalCount();
        $trashedPaymentTypes = $this->getTrashedCount();

        return PaymentTypeResource::collection(PaymentType::query()
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                return $query->where('name', 'LIKE', '%' . request('search') . '%')
                             ->orWhere('code', 'LIKE', '%' . request('search') . '%')
                             ->orWhere('description', 'LIKE', '%' . request('search') . '%');
            })
            ->when(request('active'), function ($query) {
                $active = request('active');
                if ($active === 'Active') {
                    $query->where('is_active', 1);
                } elseif ($active === 'Inactive') {
                    $query->where('is_active', 0);
                }
            })
            ->when(request('order'), function ($query) {
                return $query->orderBy(request('order'), request('sort'));
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allPaymentTypes, 'trashed' => $trashedPaymentTypes]]);
    }

    /**
     * Get active payment types for dropdown
     */
    public function getActivePaymentTypes()
    {
        return PaymentType::active()
            ->select('id', 'name', 'code', 'description')
            ->orderBy('name')
            ->get();
    }
}
