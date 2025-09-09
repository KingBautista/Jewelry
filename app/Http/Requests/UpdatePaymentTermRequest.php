<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentTermId = $this->route('id');
        
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_terms', 'code')->ignore($paymentTermId)
            ],
            'down_payment_percentage' => 'required|numeric|min:0|max:100',
            'remaining_percentage' => 'required|numeric|min:0|max:100',
            'term_months' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'schedules' => 'nullable|array',
            'schedules.*.month_number' => 'required|integer|min:1|max:60',
            'schedules.*.percentage' => 'required|numeric|min:0|max:100',
            'schedules.*.description' => 'nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that down payment + remaining = 100%
            $downPayment = $this->input('down_payment_percentage');
            $remaining = $this->input('remaining_percentage');
            
            if ($downPayment && $remaining) {
                $total = $downPayment + $remaining;
                if (abs($total - 100) > 0.01) {
                    $validator->errors()->add('payment_breakdown', 'Down payment and remaining percentages must add up to exactly 100%.');
                }
            }

            // Validate schedules if provided
            $schedules = $this->input('schedules', []);
            if (!empty($schedules)) {
                $termMonths = $this->input('term_months');
                $remainingPercentage = $this->input('remaining_percentage');
                
                // Check if number of schedules matches term months
                if (count($schedules) !== $termMonths) {
                    $validator->errors()->add('schedules', "Number of schedules (" . count($schedules) . ") must match term months ({$termMonths}).");
                }
                
                // Check if schedule percentages add up to remaining percentage
                $totalSchedulePercentage = array_sum(array_column($schedules, 'percentage'));
                if (abs($totalSchedulePercentage - $remainingPercentage) > 0.01) {
                    $validator->errors()->add('schedules', "Schedule percentages ({$totalSchedulePercentage}%) must add up to remaining percentage ({$remainingPercentage}%).");
                }
                
                // Check for duplicate month numbers
                $monthNumbers = array_column($schedules, 'month_number');
                if (count($monthNumbers) !== count(array_unique($monthNumbers))) {
                    $validator->errors()->add('schedules', 'Month numbers must be unique.');
                }
                
                // Validate month numbers are sequential starting from 1
                sort($monthNumbers);
                for ($i = 0; $i < count($monthNumbers); $i++) {
                    if ($monthNumbers[$i] !== $i + 1) {
                        $validator->errors()->add('schedules', 'Month numbers must be sequential starting from 1.');
                        break;
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Payment term name is required.',
            'code.required' => 'Payment term code is required.',
            'code.unique' => 'Payment term code already exists.',
            'down_payment_percentage.required' => 'Down payment percentage is required.',
            'down_payment_percentage.numeric' => 'Down payment percentage must be a number.',
            'down_payment_percentage.min' => 'Down payment percentage must be at least 0.',
            'down_payment_percentage.max' => 'Down payment percentage cannot exceed 100%.',
            'remaining_percentage.required' => 'Remaining percentage is required.',
            'remaining_percentage.numeric' => 'Remaining percentage must be a number.',
            'remaining_percentage.min' => 'Remaining percentage must be at least 0.',
            'remaining_percentage.max' => 'Remaining percentage cannot exceed 100%.',
            'term_months.required' => 'Term months is required.',
            'term_months.integer' => 'Term months must be an integer.',
            'term_months.min' => 'Term months must be at least 1.',
            'term_months.max' => 'Term months cannot exceed 60.',
            'schedules.*.month_number.required' => 'Month number is required for each schedule.',
            'schedules.*.month_number.integer' => 'Month number must be an integer.',
            'schedules.*.month_number.min' => 'Month number must be at least 1.',
            'schedules.*.month_number.max' => 'Month number cannot exceed 60.',
            'schedules.*.percentage.required' => 'Percentage is required for each schedule.',
            'schedules.*.percentage.numeric' => 'Percentage must be a number.',
            'schedules.*.percentage.min' => 'Percentage must be at least 0.',
            'schedules.*.percentage.max' => 'Percentage cannot exceed 100%.',
        ];
    }
}