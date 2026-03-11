<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CourierEarningsController extends Controller
{
    /**
     * Display a summary of driver earnings and delivery history.
     * * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = auth()->user();
        $wallet = $user->wallet;

        // 01. Total earnings filtered by completed delivery commissions
        $totalEarnings = $wallet->transactions()
        ->where('source', 'delivery_commission')
        ->where('type', 'credit')
        ->where('status', 'completed')
        ->sum('amount');

        // 02. Month-on-Month Commission Comparison
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        
        $thisMonthCommission = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])
            ->sum('amount');
        
        $lastMonthCommission = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        
        $percentChange = $lastMonthCommission > 0 
            ? (($thisMonthCommission - $lastMonthCommission) / $lastMonthCommission) * 100 
            : 0;
        
        $commissionCompare = [
            'this_month_commission' => $thisMonthCommission,
            'last_month_commission' => $lastMonthCommission,
            'percent_change' => round($percentChange, 2),
        ];

        // 3. Delivery Metrics
        $completedDeliveries = $user->assignedOrders()
        ->where('status', 'delivered')
        ->count();

        // 04.1. Earning Trend Weekly

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weeklyEarnings = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DAYNAME(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderByRaw('MIN(created_at)')
            ->get();

        // 04.2. Earning Trend Monthly
        $startOfYear = Carbon::now()->subMonths(11)->startOfMonth();

        $monthlyEarnings = $wallet->transactions()
            ->where('source', 'delivery_commission')
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfYear)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month_num, MONTHNAME(created_at) as month, SUM(amount) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at), MONTHNAME(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get();

        $earningTrend = [
            'weekly_earnings' => $weeklyEarnings,
            'monthly_earnings' => $monthlyEarnings,
        ];

        // 05. Available Balance
        $availableBalance = $wallet->balance;

        // 06. Delivery History
        $deliveryHistory = $user->assignedOrders()
            ->where('status', 'delivered')
            ->with('walletTransaction:id,wallet_id,order_id,amount')
            ->get()
            ->makeHidden(['courier_commission', 'admin_commission']);

        // Prepare data
        $data = [
            'total_earnings' => $totalEarnings,
            'commission_compare' => $commissionCompare,
            'completed_deliveries' => $completedDeliveries,
            'earning_trend' => $earningTrend,
            'available_balance' => $availableBalance,
            'delivery_history' => $deliveryHistory
        ];

        // Return
        return apiSuccess('Earnings dashboard retrieved successfully', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
