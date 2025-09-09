<?php

namespace App\Services;

use App\Models\Tax;
use App\Http\Resources\TaxResource;

class TaxService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TaxResource(new Tax), new Tax());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allTaxes = $this->getTotalCount();
        $trashedTaxes = $this->getTrashedCount();

        return TaxResource::collection(Tax::query()
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
            ->when(request('order'), function ($query) {
                return $query->orderBy(request('order'), request('sort'));
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allTaxes, 'trashed' => $trashedTaxes]]);
    }

    /**
     * Get active taxes for dropdown
     */
    public function getActiveTaxes()
    {
        return Tax::active()
            ->select('id', 'name', 'code', 'rate')
            ->orderBy('name')
            ->get();
    }
}
