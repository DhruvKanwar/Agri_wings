<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\AssetOperator;
use App\Models\Battery;
use App\Models\Crop;
use App\Models\FarmerDetails;
use App\Models\Services;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;



class DashboardController extends Controller
{
    //
    public function get_fleet_management_details()
    {
        $topOperators = Services::with(['assetOperator' => function ($query) {
            $query->select('id', 'name', 'phone')
                ->where('status', 1);
        }])
            ->select('asset_operator_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status', '!=', 0)
            ->where('order_status','>',1)
            ->groupBy('asset_operator_id')
            ->orderByDesc('total_sprayed_acreage')
            ->limit(5)
            ->get();


        $data['top_five_operators'] = $topOperators;

        $topAssets = Services::with(['asset' => function ($query) {
            $query->select('id', 'asset_id', 'uin')
                ->where('status', 1);
        }])
            ->select('asset_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status', '!=', 0)
            ->where('order_status', '>', 1)
            ->groupBy('asset_id')
            ->orderByDesc('total_sprayed_acreage')
            ->limit(5)
            ->get();


        $data['top_five_operators'] = $topOperators;
        $data['top_five_assets'] = $topAssets;

        $data['static_report'] = [
            'total_farmers' => FarmerDetails::where('status', 1)->count(),
            'total_batteries' => Battery::where('status', 1)->count(),
            'total_orders' => Services::where('order_status','!=' ,0)->count(),
            'total_crops' => Crop::where('status', 1)->count(),
            'total_assets' => AssetDetails::where('status', 1)->count(),
            'total_vehicles' => Vehicle::where('status', 1)->count(),
            'total_operators' => AssetOperator::where('status', 1)->count(),
        ];

        $currentMonth = date('n');
        $currentYear = date('Y');
        $startMonth = 4; // April is month 4

        // Adjusting the year for April to March financial year
        if ($currentMonth < $startMonth) {
            $startYear = $currentYear - 1;
        } else {
            $startYear = $currentYear;
        }

        $endYear = $startYear + 1;
        $endMonth = 3; // March is month 3

        $monthlyDetails = Services::select(
            DB::raw('DATE_FORMAT(order_date, "%M") as month'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage')
        )
        ->where('order_status', '!=', 0)
        ->where(function ($query) use ($startMonth, $startYear, $endMonth, $endYear) {
            $query->where(function ($query) use ($startMonth, $startYear) {
                $query->whereYear('order_date', '=', $startYear)
                    ->whereMonth('order_date', '>=', $startMonth);
            })
            ->orWhere(function ($query) use ($endMonth, $endYear) {
                $query->whereYear('order_date', '=', $endYear)
                    ->whereMonth('order_date', '<=', $endMonth);
            });
        })
            ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%M")'))
            ->get();

        // Create an array to hold monthly data
        $month_wise_acreage = [];

        // Fill in data for each month from April to March of the following year
        for ($i = 4; $i <= 15; $i++) {
            $month = ($i % 12 == 0) ? 12 : $i % 12;
            $year = ($i <= 12) ? $startYear : $endYear;
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $monthData = $monthlyDetails->where('month', $monthName)->first();

            if ($monthData) {
                $month_wise_acreage[] = [
                    'month' => $monthName,
                    'total_requested_acreage' => $monthData->total_requested_acreage,
                    'total_sprayed_acreage' => $monthData->total_sprayed_acreage,
                ];
            } else {
                $month_wise_acreage[] = [
                    'month' => $monthName,
                    'total_requested_acreage' => 0,
                    'total_sprayed_acreage' => 0,
                ];
            }
        }

        $data['month_wise_acreage'] = $month_wise_acreage;





        $client_requested_acerage = Services::with(['clientDetails' => function ($query) {
            $query->select('id', 'regional_client_name')->where('status', 1);
        }])
            ->select('client_id', DB::raw('SUM(requested_acreage) as total_requested_acreage'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status','!=',0)
            ->groupBy('client_id')
            ->orderByDesc('total_requested_acreage')
            ->get();
        $data['client_acerage'] = $client_requested_acerage;

   


        $crop_wise_acerage = Services::with(['crop' => function ($query) {
            $query->select('id', 'crop_name')
                ->where('status', 1);
        }])
            ->select('crop_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status', '!=', 0)
            ->groupBy('crop_id')
            ->orderByDesc('total_sprayed_acreage')
            ->get();
        $data['crop_wise_acerage'] = $crop_wise_acerage;

        // $client_wise_sprayed_acerage = Services::with([
        //     'clientDetails' => function ($query) {
        //         $query->select('id', 'regional_client_name')
        //             ->where('status', 1);
        //     }
        // ])
        //     ->select('client_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
        //     ->where('order_status', '!=', 0)
        //     ->groupBy('client_id')
        //     ->orderByDesc('total_sprayed_acreage')
        //     ->get();

        // $data['client_wise_sprayed_acerage'] = $client_wise_sprayed_acerage;

        $state_wise_sprayed_acreage = Services::with([
            'clientDetails' => function ($query) {
                $query->select('id', 'state')
                    ->where('status', 1);
            }
        ])
            ->select('regional_clients.state', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->join('regional_clients', 'services.client_id', '=', 'regional_clients.id')
            ->where('services.order_status', '!=', 0)
            ->groupBy('regional_clients.state')
            ->orderByDesc('total_sprayed_acreage')
            ->get();

        $state_wise = [];
        foreach ($state_wise_sprayed_acreage as $item) {
            $state = $item->state;
            $state_wise[] = [
                'total_sprayed_acreage' => $item->total_sprayed_acreage,
                'client_details' => [
                    'state' => $state,
                ]
            ];
        }

    
        $data['state_wise_sprayed_acreage'] = $state_wise;



        // return [$data['client_requested_acerage'], $data['client_sprayed_acerage']];

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Data Fetched Successfully',
            'data' => $data
        );

        return response()->json($result_array, 200);
    }

    public function get_cso_dashboard_details()
    {
        $details = Auth::user();
        $get_user_data = User::where('id', $details->id)->first();
        if ($get_user_data->role == 'cso') {
            // $user_id = $get_user_data->id;
            $explode_client_ids = explode(',', $get_user_data->client_id);

        }else{
            $result_array = array(
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Not a valid user...',
                'data' => []
            );

            return response()->json($result_array, 200);
        }

        $cso_total_acreage = Services::select(
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage'),
        )
        ->whereNotIn('order_status', [0])
        ->whereIn('client_id', $explode_client_ids)
        ->get();


        $data['cso_total_acreage'] = $cso_total_acreage;
        $todays_acreage_details = Services::select(
            DB::raw('DATE_FORMAT(order_date, "%d-%m-%Y") as date'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage'),
        )
        ->whereNotIn('order_status', [0])
        ->whereIn('client_id', $explode_client_ids)
        ->whereDate('order_date', '=', date('Y-m-d')) // Filter by current date
        ->groupBy('date')
        ->get();

        $data['todays_acreage_details'] = $todays_acreage_details;


        // $currentMonth = date('n');
        // $currentYear = date('Y');
        // $startMonth = 4; // April is month 4

        // // Adjusting the year for April to March financial year
        // if ($currentMonth < $startMonth) {
        //     $startYear = $currentYear - 1;
        // } else {
        //     $startYear = $currentYear;
        // }

        // $endYear = $startYear + 1;
        // $endMonth = 3; // March is month 3

        // $monthlyDetails_acreage = Services::select(
        //     DB::raw('DATE_FORMAT(order_date, "%M") as month'),
        //     DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
        //     DB::raw('SUM(requested_acreage) as total_requested_acreage')
        // )
        // ->whereNotIn('order_status', [0])
        // ->whereIn('client_id', $explode_client_ids)
        // ->where(function ($query) use ($startMonth, $startYear, $endMonth, $endYear) {
        //     $query->whereYear('order_date', '=', $startYear)
        //         ->whereMonth('order_date', '>=', $startMonth)
        //         ->orWhere(function ($query) use ($endMonth, $endYear) {
        //             $query->whereYear('order_date', '=', $endYear)
        //                 ->whereMonth('order_date', '<=', $endMonth);
        //         });
        // })
        //     ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%M")'))
        //     ->get();

        // $data['month_wise_acreage'] = $monthlyDetails_acreage->isEmpty() ? [] : $monthlyDetails_acreage;


        $currentMonth = date('n');
        $currentYear = date('Y');
        $startMonth = 4; // April is month 4

        // Adjusting the year for April to March financial year
        if ($currentMonth < $startMonth) {
            $startYear = $currentYear - 1;
        } else {
            $startYear = $currentYear;
        }

        $endYear = $startYear + 1;
        $endMonth = 3; // March is month 3

        $monthlyDetails_acreage = Services::select(
            DB::raw('DATE_FORMAT(order_date, "%M") as month'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage')
        )
        ->whereNotIn('order_status', [0])
        ->whereIn('client_id', $explode_client_ids)
        ->where(function ($query) use ($startMonth, $startYear, $endMonth, $endYear) {
            $query->where(function ($query) use ($startMonth, $startYear) {
                $query->whereYear('order_date', '=', $startYear)
                    ->whereMonth('order_date', '>=', $startMonth);
            })
            ->orWhere(function ($query) use ($endMonth, $endYear) {
                $query->whereYear('order_date', '=', $endYear)
                    ->whereMonth('order_date', '<=', $endMonth);
            });
        })
            ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%M")'))
            ->get();

        // Create an array to hold monthly data
        $month_wise_acreage = [];

        // Fill in data for each month
        for ($i = 4; $i <= 15; $i++) { // Loop from April of last year to March of next year
            $month = ($i % 12 == 0) ? 12 : $i % 12;
            $year = ($i <= 12) ? $startYear : $endYear;
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $monthData = $monthlyDetails_acreage->where('month', $monthName)->first();

            if ($monthData) {
                $month_wise_acreage[] = [
                    'month' => $monthName,
                    'total_sprayed_acreage' => $monthData->total_sprayed_acreage,
                    'total_requested_acreage' => $monthData->total_requested_acreage,
                ];
            } else {
                $month_wise_acreage[] = [
                    'month' => $monthName,
                    'total_sprayed_acreage' => 0,
                    'total_requested_acreage' => 0,
                ];
            }
        }

        $data['month_wise_acreage'] = $month_wise_acreage;





        $client_wise_sprayed_acerage = Services::with([
            'clientDetails' => function ($query) {
                $query->select('id', 'regional_client_name')
                ->where('status', 1);
            }
        ])
            ->select('client_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status', '!=', 0)
            ->whereIn('client_id', $explode_client_ids)
            ->groupBy('client_id')
            ->orderByDesc('total_sprayed_acreage')
            ->get();

        $data['client_wise_sprayed_acerage'] = $client_wise_sprayed_acerage;


        // return [$data['client_requested_acerage'], $data['client_sprayed_acerage']];

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Data List Fetched Successfully',
            'data' => $data
        );

        return response()->json($result_array, 200);
    }

    public function get_management_dashboard_details()
    {
        $details = Auth::user();
        $get_user_data = User::where('id', $details->id)->first();
        if ($get_user_data->role != 'management') {
            $result_array = array(
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Not a valid user...',
                'data' => []
            );

            return response()->json($result_array, 200);
        }



        $total_acreage = Services::select(
            DB::raw('SUM(requested_acreage) as total_requested_acreage'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
        )
            ->whereNotIn('order_status', [0])
            ->get();

        $data['total_acreage'] = $total_acreage;


        $lastThirtyDaysStart = Carbon::now()->subDays(29)->startOfDay(); // Start of the day 30 days ago
        $lastThirtyDaysEnd = Carbon::now()->endOfDay(); // End of the current day

        $lastThirtyDaysDetails = Services::select(
            DB::raw('DATE_FORMAT(order_date, "%M") as month'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage')
        )
        ->whereNotIn('order_status', [0])
        ->whereBetween('order_date', [$lastThirtyDaysStart, $lastThirtyDaysEnd])
        ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%M")'))
        ->get();

        $data['last_thirty_days_acreage'] = $lastThirtyDaysDetails;

        $lastWeekStart = Carbon::now()->subDays(6)->startOfDay(); // Start of the day 7 days ago
        $lastWeekEnd = Carbon::now()->endOfDay(); // End of the current day

        $lastWeekDetails = Services::select(
            DB::raw('DATE_FORMAT(order_date, "%M") as month'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage')
        )
        ->whereNotIn('order_status', [0])
        ->whereBetween('order_date', [$lastWeekStart, $lastWeekEnd])
        ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%M")'))
        ->get();

        $data['last_week_acreage'] = $lastWeekDetails;


        $allTimeAverageOrderSize = Services::select(
            DB::raw('SUM(requested_acreage) / COUNT(*) as average_order_size'),
              DB::raw('SUM(requested_acreage) as requested_acerage'),
              DB::raw('SUM(sprayed_acreage) as sprayed_acerage'),
            DB::raw('COUNT(*) as total_no_of_orders'),

        )
        ->whereNotIn('order_status', [0])
        ->get();

        $data['all_time_average_order_size'] = $allTimeAverageOrderSize;

        $lastSevenDaysAverageOrderSize = Services::select(
            DB::raw('SUM(requested_acreage) / COUNT(*) as average_order_size'),
            DB::raw('SUM(requested_acreage) as requested_acerage'),
            DB::raw('SUM(sprayed_acreage) as sprayed_acerage'),
            DB::raw('COUNT(*) as total_no_of_orders'),

        )
        ->whereNotIn('order_status', [0])
        ->whereBetween('order_date', [$lastWeekStart, $lastWeekEnd])
        ->get();

        $data['last_seven_days_average_order_size'] = $lastSevenDaysAverageOrderSize;

        $lastThirtyDaysStart = Carbon::now()->subDays(29)->startOfDay(); // Start of the day 30 days ago
        $lastThirtyDaysEnd = Carbon::now()->endOfDay(); // End of the current day

        $lastThirtyDaysAverageOrderSize = Services::select(
            DB::raw('SUM(requested_acreage) / COUNT(*) as average_order_size'),
            DB::raw('SUM(requested_acreage) as requested_acerage'),
            DB::raw('SUM(sprayed_acreage) as sprayed_acerage'),
            DB::raw('COUNT(*) as total_no_of_orders'),
           
        )
        ->whereNotIn('order_status', [0])
        ->whereBetween('order_date', [$lastThirtyDaysStart, $lastThirtyDaysEnd])
        ->get();

        $data['last_thirty_days_average_order_size'] = $lastThirtyDaysAverageOrderSize;




        $clientWiseBifurcation = Services::with([
            'clientDetails' => function ($query) {
                $query->select('id', 'regional_client_name')
                    ->where('status', 1);
            }
        ])->select(
            'client_id',
            DB::raw('SUM(sprayed_acreage) / SUM(requested_acreage)  as client_wise_bifurcation')
        )
        ->whereNotIn('order_status', [0])
        ->groupBy('client_id')
        ->get();

        $data['client_wise_bifurcation'] = $clientWiseBifurcation;



        $totalFarmersServed = Services::select(
            DB::raw('COUNT(DISTINCT farmer_id) as total_farmers_served')
        )
        ->whereNotIn('order_status', [0])
        ->value('total_farmers_served');

        $data['total_farmers_served'] = $totalFarmersServed ?? 0;

        $data['our_fleet'] = [
            'total_assets' => AssetDetails::where('status', 1)->count(),
            'total_vehicles_owned' => Vehicle::whereNotNull('operator_id')->orWhere('operator_id', '')->count(),
            'total_operators' => AssetOperator::where('status', 1)->count(),
        ];



        // return [$data['client_requested_acerage'], $data['client_sprayed_acerage']];

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Data Fetched Successfully',
            'data' => $data
        );

        return response()->json($result_array, 200);
    }
}
