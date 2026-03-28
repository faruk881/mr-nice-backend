<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardStatsController extends Controller
{
    public function index() 
    {   
        // 01. Total pending orders overall
        $totalPendingOrdersAllTime = Order::where('status', 'pending')->count();

        // 01.1 Total pending orders in the last three months
        $totalPendingOrdersLastThreeMonths = Order::where('status', 'pending')
        ->where('created_at', '>=', Carbon::now()->subMonths(3))
        ->count();

        // 02. Total pending orders overall
        $totalCompletedOrdersAllTime = Order::where('status', 'delivered')->count();

        // 02.2 Total pending orders in the last three months
        $totalCompletedOrdersLastThreeMonths = Order::where('status', 'delivered')
        ->where('created_at', '>=', Carbon::now()->subMonths(3))
        ->count();

        // Query users
        $query = User::query();

        // 03. Get total active customers
        $totalActiveCustomers = (clone $query)->whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        })->count();
        
        $totalActiveCouriers = (clone $query)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'courier');
            })
            ->whereHas('courierProfile', function ($q) {
                $q->where('document_status', 'verified');
            })
            ->count();

        $wallet = auth()->user()->wallet;

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Get daily earnings for the week
        $earnings = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DATE(created_at) as date, DAYNAME(created_at) as day, SUM(amount) as total')
            ->groupBy('date','day')
            ->orderByRaw('MIN(created_at)')
            ->get();

        // 05. Total weekly earnings
        $totalWeeklyEarnings = round((float) $earnings->sum('total'), 2);

        // 06. Weekly earnings per day
        $weeklyEarnings = $earnings;

        // 07. This Month Commission
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalThisMonthEarning = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // 08. Last 6 Months Commission
        $startLastSixMonths = Carbon::now()->subMonths(6)->startOfMonth();
        $endNow = Carbon::now()->endOfMonth();

        $totalLastSixMonthsEarning = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startLastSixMonths, $endNow])
            ->sum('amount');

        // 09. Total This Year Commission
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $totalThisYearEarning = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->sum('amount');

        $recentFivePnedingOrders = Order::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();


        // Prepare the data
        $data = [
            'total_pending_orders_all_time' => $totalPendingOrdersAllTime,
            'total_pending_orders_last_three_months' => $totalPendingOrdersLastThreeMonths,
            'total_completed_orders_all_time' => $totalCompletedOrdersAllTime,
            'total_completed_orders_last_three_months' => $totalCompletedOrdersLastThreeMonths,
            'total_active_customers' => $totalActiveCustomers,
            'total_active_couriers' => $totalActiveCouriers,
            'total_weekly_earnings' => $totalWeeklyEarnings,
            'total_this_month_earning' => $totalThisMonthEarning,
            'total_last_six_months_earning' => $totalLastSixMonthsEarning,
            'total_last_year_earning' => $totalThisYearEarning,
            'weekly_earnings' => $weeklyEarnings,
            'recent_five_pending_orders' => $recentFivePnedingOrders,
            
        ];

        // Return the data
        return apiSuccess('Dashboard Stats', $data);
    }
}

