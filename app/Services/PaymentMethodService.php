<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;

class PaymentMethodService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new PaymentMethodResource(new PaymentMethod), new PaymentMethod());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allPaymentMethods = $this->getTotalCount();
        $trashedPaymentMethods = $this->getTrashedCount();

        return PaymentMethodResource::collection(PaymentMethod::query()
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                return $query->where('bank_name', 'LIKE', '%' . request('search') . '%')
                             ->orWhere('account_name', 'LIKE', '%' . request('search') . '%')
                             ->orWhere('account_number', 'LIKE', '%' . request('search') . '%');
            })
            ->when(request('active'), function ($query) {
                $active = request('active');
                if ($active === 'Active') {
                    $query->where('active', 1);
                } elseif ($active === 'Inactive') {
                    $query->where('active', 0);
                }
            })
            ->when(request('order'), function ($query) {
                return $query->orderBy(request('order'), request('sort'));
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allPaymentMethods, 'trashed' => $trashedPaymentMethods]]);
    }

    /**
     * Get active payment methods for dropdown
     */
    public function getActivePaymentMethods()
    {
        return PaymentMethod::active()
            ->select('id', 'bank_name', 'account_name', 'account_number')
            ->orderBy('bank_name')
            ->get();
    }
}
