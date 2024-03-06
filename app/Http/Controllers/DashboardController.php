<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\AssetOperator;
use App\Models\Crop;
use App\Models\FarmerDetails;
use App\Models\Services;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
        ->groupBy('asset_operator_id')
        ->orderByDesc('total_sprayed_acreage')
        ->limit(5)
        ->get();


        $data['top_five_operators']= $topOperators;

        $topAssets = Services::with(['asset' => function ($query) {
            $query->select('id', 'asset_id', 'uin')
            ->where('status', 1);
        }])
            ->select('asset_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status', '!=', 0)
            ->groupBy('asset_id')
            ->orderByDesc('total_sprayed_acreage')
            ->limit(5)
            ->get();


        $data['top_five_operators'] = $topOperators;
        $data['top_five_assets'] = $topAssets;

        $data['static_report'] = [
            'total_farmers' => FarmerDetails::where('status', 1)->count(),
            'total_crops' => Crop::where('status', 1)->count(),
            'total_assets' => AssetDetails::where('status', 1)->count(),
            'total_vehicles' => Vehicle::where('status', 1)->count(),
            'total_operators' => AssetOperator::where('status', 1)->count(),
        ];

        $monthlyDetails = Services::select(
            DB::raw('DATE_FORMAT(delivery_date, "%M") as month'),
            DB::raw('SUM(requested_acreage) as total_requested_acreage'),
            DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage')
        )
        ->where('order_status', 6)
        ->whereYear('delivery_date', '=', date('Y')) // Filter by current year
        ->groupBy(DB::raw('MONTH(delivery_date)'), DB::raw('DATE_FORMAT(delivery_date, "%M")'))
        ->get();

        $data['month_wise_acreage'] = $monthlyDetails;

        $client_requested_acerage = Services::with(['clientDetails' => function ($query) {
            $query->select('id', 'regional_client_name')->where('status', 1);
        }])
        ->select('client_id', DB::raw('SUM(requested_acreage) as total_requested_acreage'))
        ->whereNotIn('order_status', [0, 6])
        ->groupBy('client_id')
        ->orderByDesc('total_requested_acreage')
        ->get();
        $data['client_requested_acerage'] = $client_requested_acerage;

        $client_sprayed_acerage = Services::with(['clientDetails' => function ($query) {
            $query->select('id', 'regional_client_name')
            ->where('status', 1);
        }])
            ->select('client_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
            ->where('order_status', '=', 6)
            ->groupBy('client_id')
            ->orderByDesc('total_sprayed_acreage')
            ->get();
        $data['client_sprayed_acerage']= $client_sprayed_acerage;


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

        $state_wise_sprayed_acerage = Services::with([
            'clientDetails' => function ($query) {
                $query->select('id', 'state')
                ->where('status', 1);
            }
        ])
        ->select('client_id', DB::raw('SUM(sprayed_acreage) as total_sprayed_acreage'))
        ->where('order_status', '!=', 0)
        ->groupBy('client_id')
        ->orderByDesc('total_sprayed_acreage')
        ->get();
        
        $data['state_wise_sprayed_acerage'] = $state_wise_sprayed_acerage;

        // return [$data['client_requested_acerage'], $data['client_sprayed_acerage']];

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Users List Fetched Successfully',
            'data' => $data
        );

        return response()->json($result_array, 200);
    }
}
