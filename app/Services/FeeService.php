<?php

namespace App\Services;

use App\Models\Fee;
use App\Http\Resources\FeeResource;

class FeeService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new FeeResource(new Fee), new Fee());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allFees = $this->getTotalCount();
        $trashedFees = $this->getTrashedCount();

        return FeeResource::collection(Fee::query()
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
        )->additional(['meta' => ['all' => $allFees, 'trashed' => $trashedFees]]);
    }

    /**
     * Get active fees for dropdown
     */
    public function getActiveFees()
    {
        return Fee::active()
            ->select('id', 'name', 'code', 'amount', 'type')
            ->orderBy('name')
            ->get();
    }
}
