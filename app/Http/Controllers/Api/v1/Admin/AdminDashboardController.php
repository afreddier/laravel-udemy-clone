<?php

namespace App\Http\Controllers\Api\v1\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Payment;
use App\Models\Course;
use App\Models\Payout;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    


    public function fetchAdminSalesChartData(Request $request)
    {
        $filter = $request->period;
        $periods = collect($this->generateDateRange(Carbon::now()->subDays($filter), Carbon::now()));
        $times = [];
        foreach($periods as $p){
            $times[$p] = 0;
        }
        $data = [];
        // Total Sales
        $sales = Payment::where('created_at', '>=', Carbon::now()->subDays($filter))
                        ->whereNull('refunded_at')
                        ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('sum(amount) as total'))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
        $total_sales = [];
        foreach($sales as $val){
            $total_sales[$val->date] = (float)$val->total;
        }
        $grand_total = array_merge($times, $total_sales);
        array_push($data, [
            'name' => 'Total Sales',
            'data' => $grand_total
        ]);

        // get sales minus author commission
        $platform_earnings = Payment::where('created_at', '>=', Carbon::now()->subDays($filter))
                        ->whereNull('refunded_at')
                        ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('sum(amount - author_earning - affiliate_earning) as total'))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
        
        $total_earnings = [];
        foreach($platform_earnings as $val){
            $total_earnings[$val->date] = (float)$val->total;
        }
        $grand_earnings = array_merge($times, $total_earnings);
        array_push($data, [
            'name' => 'Platform Earnings',
            'data' => $grand_earnings
        ]);

        // Total Refunds
        $refunds = Payment::where('created_at', '>=', Carbon::now()->subDays($filter))
                        ->whereNotNull('refunded_at')
                        ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('sum(amount) as total'))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
        $total_refunds = [];
        foreach($refunds as $val){
            $total_refunds[$val->date] = (float)$val->total;
        }
        $grand_refunds = array_merge($times, $total_refunds);
        array_push($data, [
            'name' => 'Total Refunds',
            'data' => $grand_refunds
        ]);

        // total sales
        $lifetime_sales = Payment::whereNull('refunded_at')->sum('amount');
        $total_platform_earnings = Payment::select(\DB::raw('sum(amount - author_earning - affiliate_earning) as total'))->first();
        $lifetime_data = [
           'lifetime_sales' => $lifetime_sales ? (float)$lifetime_sales : 0,
           'lifetime_earnings' => $total_platform_earnings ? (float)$total_platform_earnings->total : 0
        ];

        $res = [
            'chartData' => $data,
            'lifetimeData' => $lifetime_data,
        ];

        return response()->json($res, 200);

    }


    private function generateDateRange(Carbon $start_date, Carbon $end_date)
    {
        $dates = [];
    
        for($date = $start_date; $date->lte($end_date); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }
    
        return $dates;
    }


}

