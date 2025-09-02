<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get overall dashboard statistics
     */
    public function getStatistics()
    {
        $today = Carbon::today();
        
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', true)->count(),
            'today_registrations' => User::whereDate('created_at', $today)->count(),
        ];
    }
}