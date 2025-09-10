<?php

namespace App\Services;

use App\Models\PaymentTerm;
use App\Models\PaymentTermSchedule;
use App\Http\Resources\PaymentTermResource;

class PaymentTermService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new PaymentTermResource(new PaymentTerm), new PaymentTerm());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allPaymentTerms = $this->getTotalCount();
        $trashedPaymentTerms = $this->getTrashedCount();

        return PaymentTermResource::collection(PaymentTerm::query()
            ->with('schedules')
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
        )->additional(['meta' => ['all' => $allPaymentTerms, 'trashed' => $trashedPaymentTerms]]);
    }

    /**
     * Store a newly created resource with schedules.
     */
    public function storeWithSchedules(array $data, array $schedules = [])
    {
        // Validate business rules before storing
        $this->validatePaymentTermBusinessRules($data, $schedules);
        
        $paymentTerm = parent::store($data);
        
        if (!empty($schedules)) {
            foreach ($schedules as $schedule) {
                $paymentTerm->schedules()->create($schedule);
            }
        }

        return $this->resource::make($paymentTerm->load('schedules'));
    }

    /**
     * Update the specified resource with schedules.
     */
    public function updateWithSchedules(array $data, int $id, array $schedules = [])
    {
        // Validate business rules before updating
        $this->validatePaymentTermBusinessRules($data, $schedules);
        
        $paymentTerm = $this->model::findOrFail($id);
        $paymentTerm->update($data);
        
        // Delete existing schedules
        $paymentTerm->schedules()->delete();
        
        // Create new schedules
        if (!empty($schedules)) {
            foreach ($schedules as $schedule) {
                $paymentTerm->schedules()->create($schedule);
            }
        }

        return $this->resource::make($paymentTerm->load('schedules'));
    }

    /**
     * Get Details for editing the specified resource with schedules.
     */
    public function show(int $id)
    {
        $model = $this->model::with('schedules')->findOrFail($id);
        return $this->resource::make($model);
    }

    /**
     * Get active payment terms for dropdown
     */
    public function getActivePaymentTerms()
    {
        return PaymentTerm::active()
            ->with('schedules')
            ->select('id', 'name', 'code', 'down_payment_percentage', 'remaining_percentage', 'term_months')
            ->orderBy('name')
            ->get();
    }

    /**
     * Validate payment term business rules
     */
    private function validatePaymentTermBusinessRules(array $data, array $schedules = [])
    {
        // Validate that down payment + remaining = 100%
        $downPayment = $data['down_payment_percentage'] ?? 0;
        $remaining = $data['remaining_percentage'] ?? 0;
        
        if (abs(($downPayment + $remaining) - 100) > 0.01) {
            throw new \Exception('Down payment and remaining percentages must add up to exactly 100%.');
        }

        // Validate schedules if provided
        if (!empty($schedules)) {
            $termMonths = $data['term_months'] ?? 0;
            $remainingPercentage = $data['remaining_percentage'] ?? 0;
            
            // Check if number of schedules matches term months
            if (count($schedules) !== $termMonths) {
                throw new \Exception("Number of schedules (" . count($schedules) . ") must match term months ({$termMonths}).");
            }
            
            // Check if schedule percentages add up to remaining percentage
            $totalSchedulePercentage = array_sum(array_column($schedules, 'percentage'));
            if (abs($totalSchedulePercentage - $remainingPercentage) > 0.01) {
                throw new \Exception("Schedule percentages ({$totalSchedulePercentage}%) must add up to remaining percentage ({$remainingPercentage}%).");
            }
            
            // Check for duplicate month numbers
            $monthNumbers = array_column($schedules, 'month_number');
            if (count($monthNumbers) !== count(array_unique($monthNumbers))) {
                throw new \Exception('Month numbers must be unique.');
            }
            
            // Validate month numbers are sequential starting from 1
            sort($monthNumbers);
            for ($i = 0; $i < count($monthNumbers); $i++) {
                if ($monthNumbers[$i] !== $i + 1) {
                    throw new \Exception('Month numbers must be sequential starting from 1.');
                }
            }
        }
    }

    /**
     * Generate equal monthly payment schedule
     */
    public function generateEqualSchedule(int $termMonths, float $remainingPercentage)
    {
        $equalPercentage = $remainingPercentage / $termMonths;
        $schedules = [];
        
        for ($i = 1; $i <= $termMonths; $i++) {
            $schedules[] = [
                'month_number' => $i,
                'percentage' => round($equalPercentage, 2),
                'description' => "Month {$i} payment"
            ];
        }
        
        return $schedules;
    }

    /**
     * Validate payment term completeness
     */
    public function validateCompleteness(PaymentTerm $paymentTerm)
    {
        $issues = [];
        
        // Check if schedules exist
        if ($paymentTerm->schedules->isEmpty()) {
            $issues[] = 'No payment schedules defined';
        } else {
            // Check if number of schedules matches term months
            if ($paymentTerm->schedules->count() !== $paymentTerm->term_months) {
                $issues[] = "Schedule count ({$paymentTerm->schedules->count()}) doesn't match term months ({$paymentTerm->term_months})";
            }
            
            // Check if schedule percentages add up to remaining percentage
            $totalSchedulePercentage = $paymentTerm->schedules->sum('percentage');
            if (abs($totalSchedulePercentage - $paymentTerm->remaining_percentage) > 0.01) {
                $issues[] = "Schedule percentages ({$totalSchedulePercentage}%) don't match remaining percentage ({$paymentTerm->remaining_percentage}%)";
            }
        }
        
        return [
            'is_complete' => empty($issues),
            'issues' => $issues
        ];
    }
}
