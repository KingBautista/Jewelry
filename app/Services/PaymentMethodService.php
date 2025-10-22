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
     * Store a newly created payment method with QR code handling
     */
    public function store(array $data)
    {
        // Handle QR code image upload
        if (isset($data['qr_code_image']) && $data['qr_code_image']) {
            $data['qr_code_image'] = $this->handleQrCodeUpload($data['qr_code_image']);
        }

        $paymentMethod = PaymentMethod::create($data);
        return PaymentMethodResource::make($paymentMethod);
    }

    /**
     * Update payment method with QR code handling
     */
    public function update(array $data, int $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        // Handle QR code image upload
        if (isset($data['qr_code_image']) && $data['qr_code_image']) {
            // Delete old QR code image if exists
            if ($paymentMethod->qr_code_image) {
                $this->deleteQrCodeImage($paymentMethod->qr_code_image);
            }
            $data['qr_code_image'] = $this->handleQrCodeUpload($data['qr_code_image']);
        }

        $paymentMethod->update($data);
        return PaymentMethodResource::make($paymentMethod);
    }

    /**
     * Handle QR code image upload
     */
    private function handleQrCodeUpload($file)
    {
        if ($file && $file->isValid()) {
            // Generate unique filename
            $filename = 'qr_codes/' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Store the file in storage/app/public/qr_codes/
            $file->storeAs('public', $filename);
            
            return $filename;
        }
        return null;
    }

    /**
     * Delete QR code image from storage
     */
    private function deleteQrCodeImage($filename)
    {
        $filePath = storage_path('app/public/' . $filename);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Get active payment methods for dropdown
     */
    public function getActivePaymentMethods()
    {
        return PaymentMethod::active()
            ->select('id', 'bank_name', 'account_name', 'account_number', 'qr_code_image', 'description')
            ->orderBy('bank_name')
            ->get();
    }
}
