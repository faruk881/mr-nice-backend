<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminActivityStatsController extends Controller
{
    public function index()
    {
        // Use a single Carbon instance for consistency
        $now = Carbon::now();

        // ===========================
        // 01. Weekly Orders
        // ===========================
        $thisWeekStart = $now->copy()->startOfWeek();
        $thisWeekEnd = $now->copy()->endOfWeek();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();

        // 01.1 Daily breakdown of this week's orders
        $weeklyOrders = Order::whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
            ->selectRaw('DATE(created_at) as date, DAYNAME(created_at) as day, COUNT(*) as total')
            ->groupBy('date', 'day')
            ->orderBy('date')
            ->get();

        $weeklyOrders->makeHidden(['courier_commission', 'admin_commission']);

        // 01.2 Total orders
        $thisWeekTotalOrders = Order::whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();
        $lastWeekTotalOrders = Order::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        // 01.3 Completed orders
        $thisWeekCompletedOrders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
            ->count();

        $lastWeekCompletedOrders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->count();

        // 01.4 Completion rates
        $thisWeekCompletionRate = $thisWeekTotalOrders > 0
            ? ($thisWeekCompletedOrders / $thisWeekTotalOrders) * 100
            : 0;

        $lastWeekCompletionRate = $lastWeekTotalOrders > 0
            ? ($lastWeekCompletedOrders / $lastWeekTotalOrders) * 100
            : 0;

        // 01.5 Weekly trend
        $weeklyRateDifference = $thisWeekCompletionRate - $lastWeekCompletionRate;
        $weeklyTrend = $weeklyRateDifference > 0 ? 'up' : ($weeklyRateDifference < 0 ? 'down' : 'same');

        // ===========================
        // 02. Monthly Orders
        // ===========================
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // 02.1 Daily breakdown of this month's orders with pending & completed counts
        $monthlyOrders = Order::whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
            ->selectRaw("
                DATE(created_at) as date,
                DAYNAME(created_at) as day,
                SUM(CASE WHEN status IN ('pending','accepted','pickedup','pending_delivery') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed
            ")
            ->groupBy('date', 'day')
            ->orderBy('date')
            ->get();

        $monthlyOrders->makeHidden(['courier_commission', 'admin_commission']);

        // 02.2 Monthly totals
        $monthlyTotalOrders = Order::whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count();
        $monthlyCompletedOrders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
            ->count();

        $lastMonthTotalOrders = Order::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $lastMonthCompletedOrders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        // 02.3 Monthly completion rates
        $monthlyCompletionRate = $monthlyTotalOrders > 0
            ? ($monthlyCompletedOrders / $monthlyTotalOrders) * 100
            : 0;

        $previousMonthRate = $lastMonthTotalOrders > 0
            ? ($lastMonthCompletedOrders / $lastMonthTotalOrders) * 100
            : 0;

        // 02.4 Monthly trend
        $monthlyRateDifference = $monthlyCompletionRate - $previousMonthRate;
        $monthlyTrend = $monthlyRateDifference > 0 ? 'up' : ($monthlyRateDifference < 0 ? 'down' : 'same');

        // ===========================
        // 03. Recent 5 orders
        // ===========================
        $recentFivePnedingOrders = Order::where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        // ===========================
        // 04. Total pending couriers
        // ===========================
        $query = User::query();
        $totalPendingCouriers = $query->whereHas('roles', function ($q) {
            $q->where('name', 'courier');
        })->count();


        // ===========================
        // 05. Total admin earnings
        // ===========================
        $wallet = auth()->user()->wallet;
        $totalEarnings = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        // ===========================
        // 06. Earning Ratio
        // ===========================
        // Earnings for this month
        $thisMonthEarnings = $wallet->transactions()
        ->where('source', 'delivery_commission')
        ->where('type', 'credit')
        ->where('status', 'completed')
        ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
        ->sum('amount');

        // Earnings for last month
        $lastMonthEarnings = $wallet->transactions()
        ->where('source', 'delivery_commission')
        ->where('type', 'credit')
        ->where('status', 'completed')
        ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
        ->sum('amount');

        $earningDifference = $thisMonthEarnings - $lastMonthEarnings;

        // Avoid division by zero
        $earningPercentageChange = $lastMonthEarnings > 0
            ? ($earningDifference / $lastMonthEarnings) * 100
            : 0;

        // Determine text for frontend
        $earningTrendText = $earningPercentageChange > 0
            ? round($earningPercentageChange, 2) . '% more than previous month'
            : ($earningPercentageChange < 0 
                ? abs(round($earningPercentageChange, 2)) . '% less than previous month' 
                : 'Same as previous month');
        
        // ===========================
        // 06. Prepare response data
        // ===========================
        $data = [
            // Weekly stats
            'weekly_orders' => $weeklyOrders,
            'weekly_total_orders' => $thisWeekTotalOrders,
            'weekly_completed_orders' => $thisWeekCompletedOrders,
            'weekly_completion_rate' => round($thisWeekCompletionRate, 2),
            // 'previous_week_rate' => round($lastWeekCompletionRate, 2),
            'weekly_trend' => $weeklyTrend,

            // Monthly stats
            'monthly_orders' => $monthlyOrders,
            'monthly_total_orders' => $monthlyTotalOrders,
            'monthly_completed_orders' => $monthlyCompletedOrders,
            'monthly_completion_rate' => round($monthlyCompletionRate, 2),
            // 'previous_month_rate' => round($previousMonthRate, 2),
            'monthly_trend' => $monthlyTrend,
            'recent_five_pending_orders' => $recentFivePnedingOrders,
            'total_pending_couriers' => $totalPendingCouriers,
            'total_earnings' => $totalEarnings,
            'earning_percentage_change' => round($earningPercentageChange, 2),
            'earning_trend_text' => $earningTrendText,
        ];

        return apiSuccess('Activity stats loaded successfully', $data);
    }
}
