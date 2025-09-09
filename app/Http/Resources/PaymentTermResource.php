<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTermResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedules = $this->whenLoaded('schedules');
        $schedulesCount = $schedules ? $schedules->count() : 0;
        $totalSchedulePercentage = $schedules ? $schedules->sum('percentage') : 0;
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'down_payment_percentage' => $this->down_payment_percentage,
            'formatted_down_payment' => $this->formatted_down_payment,
            'remaining_percentage' => $this->remaining_percentage,
            'formatted_remaining' => $this->formatted_remaining,
            'term_months' => $this->term_months,
            'description' => $this->description,
            'active' => $this->active,
            'status' => $this->active ? 'Active' : 'Inactive',
            'schedules' => PaymentTermScheduleResource::collection($schedules),
            'schedules_count' => $schedulesCount,
            'total_schedule_percentage' => $totalSchedulePercentage,
            'is_complete' => $schedulesCount === $this->term_months && 
                           abs($totalSchedulePercentage - $this->remaining_percentage) <= 0.01,
            'completeness_issues' => $this->getCompletenessIssues($schedulesCount, $totalSchedulePercentage),
            'created_at' => $this->created_at->format('Y-m-d H:m:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
        ];
    }

    /**
     * Get completeness issues for the payment term
     */
    private function getCompletenessIssues($schedulesCount, $totalSchedulePercentage)
    {
        $issues = [];
        
        if ($schedulesCount === 0) {
            $issues[] = 'No payment schedules defined';
        } else {
            if ($schedulesCount !== $this->term_months) {
                $issues[] = "Schedule count ({$schedulesCount}) doesn't match term months ({$this->term_months})";
            }
            
            if (abs($totalSchedulePercentage - $this->remaining_percentage) > 0.01) {
                $issues[] = "Schedule percentages ({$totalSchedulePercentage}%) don't match remaining percentage ({$this->remaining_percentage}%)";
            }
        }
        
        return $issues;
    }
}