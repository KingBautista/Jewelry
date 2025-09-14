<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'revenue' => [
                'current_month' => $this->resource['revenue']['current_month'] ?? 0,
                'previous_month' => $this->resource['revenue']['previous_month'] ?? 0,
                'yearly_total' => $this->resource['revenue']['yearly_total'] ?? 0,
                'growth_percentage' => $this->calculateGrowthPercentage(
                    $this->resource['revenue']['current_month'] ?? 0,
                    $this->resource['revenue']['previous_month'] ?? 0
                ),
            ],
            'outstanding_balances' => $this->resource['outstanding_balances'] ?? 0,
            'invoice_stats' => [
                'total_issued' => $this->resource['invoice_stats']['total_issued'] ?? 0,
                'total_sent' => $this->resource['invoice_stats']['total_sent'] ?? 0,
                'total_cancelled' => $this->resource['invoice_stats']['total_cancelled'] ?? 0,
            ],
            'payment_breakdown' => $this->resource['payment_breakdown'] ?? [],
            'customer_summary' => $this->resource['customer_summary'] ?? [],
            'item_status_summary' => $this->resource['item_status_summary'] ?? [],
            'recent_activity' => $this->resource['recent_activity'] ?? [],
        ];
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowthPercentage($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
}