<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\AssetOperator;
use App\Models\Battery;
use App\Models\Crop;
use App\Models\CropPrice;
use App\Models\OrdersTimeline;
use App\Models\RegionalClient;
use App\Models\Scheme;
use App\Models\Services;
use App\Models\User;
use Aws\Api\Service;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class ServiceController extends Controller
{
    //

    public function submit_order_details(Request $request)
    {
        $rules = [
            'order_type' => 'required|string|max:100',
            'client_id' => 'nullable|string|max:200',
            'farmer_name' => 'required|string|max:250',
            'farmer_id' => 'required|string|max:100',
            'spray_date' => 'required|string|max:100',
            'crop_name' => 'required|string|max:200',
            'crop_id' => 'required|string|max:100',
            'requested_acreage' => 'required|string',
            'sprayed_acreage' => 'nullable|string',
            'farm_location' => 'required|string',
            'scheme_ids' => 'nullable|string|max:100',
            'total_discount' => 'required|string',
            'extra_discount' => 'nullable|string',
            'remarks' => 'nullable|string',
            'amount_received' => 'nullable',
            'total_amount' => 'required|string',
            'total_payable_amount' => 'required|string',
            'agriwings_discount' => 'nullable|string',
            'client_discount' => 'nullable|string',
        ];
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()]);
        }

        $data = $request->all();

        $total_discount_price = 0;
        $crop_base_price = 0;
        $total_discount = [];
        $client_discount = [];
        $agriwings_discount = 0;
        $agriwings_discount_price = 0;

        if ($data['scheme_ids'] != '') {
            $explode_scheme_ids = explode(',', $data['scheme_ids']);
            // return $explode_scheme_ids;

            foreach ($explode_scheme_ids as $scheme_id) {
                $scheme = Scheme::find($scheme_id);

                if ($scheme) {
                    $total_discount[] = $data['requested_acreage'] * $scheme->discount_price;
                    // $total_discount = $total_discount_price+$scheme->discount_price;
                    if (!empty($scheme->client_id)) {
                        // $crop_base_price = $scheme->crop_base_price;
                        $client_discount[] = $data['requested_acreage'] * $scheme->discount_price;
                    } else {
                        $agriwings_discount_price = $data['requested_acreage'] * $scheme->discount_price;
                    }
                }
            }
            // return $total_discount;
            $total_discount_sum = array_sum($total_discount);
            // return $total_discount_sum;
            $total_discount_price = $total_discount_sum;

            $total_client_discount  = array_sum($client_discount);
        } else {
            $total_discount_sum = 0;
            $total_client_discount = 0;
        }

        // return $total_client_discount;

        // if ($data['scheme_ids'] != '') {
        //     $scheme = Scheme::find($explode_scheme_ids[0]);
        //     if (empty($scheme->client_id)) {
        //         // $crop_base_price = $scheme->crop_base_price;
        //         $agriwings_discount_price = $data['requested_acreage'] * $scheme->discount_price;
        //         // return $agriwings_discount_price;

        //     }
        // }else{
        //     $agriwings_discount_price=0;
        // }

        // return [$agriwings_discount_price,$data['agriwings_discount']];

        if (!empty($data['client_id']) && $data['order_type'] == 1) {
            $get_client_details = RegionalClient::where('id', $data['client_id'])->first();
            // return $get_client_details;
            $client_state = $get_client_details->state;
            $fetch_price = CropPrice::select('state_price')->where('crop_id', $data['crop_id'])->where('state', $client_state)->first();
            //    return $client_state;
            if (!empty($fetch_price)) {
                $crop_base_price = $fetch_price->state_price;
            } else {
                $fetch_price = Crop::select('base_price')->where('id', $data['crop_id'])->first();
                $crop_base_price = $fetch_price->base_price;
            }
        } elseif ($data['order_type'] == 4 || $data['order_type'] == 5) {

            $scheme = Scheme::find($explode_scheme_ids[0]);
            // return $scheme;
            $crop_base_price = $scheme->crop_base_price;
        }


        if (isset($data['extra_discount'])) {
            $total_discount_price = $total_discount_sum + $data['extra_discount'];
            $agriwings_discount = $agriwings_discount_price + $data['extra_discount'];
        } else {
            $agriwings_discount = $agriwings_discount_price;
        }



        $total_amount = $crop_base_price * $data['requested_acreage'];
        // return [$data['total_discount'], $total_discount_price, $data['total_amount'], $total_amount, $crop_base_price, $data['order_type']];
        // return [number_format($data['total_discount'], 3),gettype(number_format($data['total_discount'], 3)), strval(number_format($total_discount_price, 3)),gettype(strval(number_format($total_discount_price, 3)))];
        // return [gettype($data['total_discount']),gettype($total_discount_price), gettype($data['total_amount']), gettype($total_amount),gettype($crop_base_price), gettype($data['order_type'])];
        // return [$total_discount_price, $total_amount];
        if (number_format($data['total_discount'], 3) != strval(number_format($total_discount_price, 3)) || number_format($data['total_amount'], 3) != strval(number_format($total_amount, 3))) {
            return response()->json(['msg' => 'Calculation of total discount or total amount not matching', 'status' => 'error', 'statuscode' => '200']);
        }

        $total_payable = $total_amount - $total_discount_price;
        // return [$data['total_discount'], $total_discount_price, $data['total_amount'], $total_amount, $total_payable];

        // return [$data['total_payable_amount'], $total_payable];
        if (number_format($data['total_payable_amount'], 3)  != strval(number_format($total_payable, 3))) {
            return response()->json(['msg' => 'Total Payable is not matching', 'status' => 'error', 'statuscode' => '200']);
        }
        //    return [$agriwings_discount, $data['agriwings_discount']];    
        if (number_format($data['agriwings_discount'], 3) != strval(number_format($agriwings_discount, 3))) {
            return response()->json(['msg' => 'Agriwings Discount is not matching', 'status' => 'error', 'statuscode' => '200']);
        }

        // return $total_client_discount;

        if (number_format($data['client_discount'], 3) != strval(number_format($total_client_discount, 3))) {
            return response()->json(['msg' => 'Total Client Discount is not matching', 'status' => 'error', 'statuscode' => '200']);
        }

        // return [$total_discount_price,$total_amount];


        // Query the database to get the latest farmer code for the state
        $latest_order_id = Services::select('order_id')
            ->orderBy('id', 'desc')
            ->first();

        // Generate the new farmer code
        if (empty($latest_order_id)) {
            $data['order_id'] = 'Order-000001';
        } else {
            $parts = explode('-', $latest_order_id->order_id);
            $lastNumber = end($parts);
            $nextNumber = (int)$lastNumber + 1;
            $formattedNextNumber = sprintf('%05d', $nextNumber);
            $data['order_id'] = 'Order-' . $formattedNextNumber;
        }
        $data['order_date'] = date('Y-m-d');


        // $data['order']

        $service = Services::create($data);

        if ($service) {
            $details = Auth::user();
            $order_timeline['created_by_id'] = $details->id;
            $order_timeline['created_by'] = $details->name;
            $order_timeline['order_date'] =   date('Y-m-d');

            $update_order_timeline = OrdersTimeline::create($order_timeline);
            if ($update_order_timeline) {
                Services::where('id', $service->id)->update(['order_details_id' => $update_order_timeline->id]);
            }
        }

        return response()->json(['msg' => 'Service created successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $service]);
    }

    public function apply_order_scheme(Request $request)
    {
        $rules = [
            'order_type' => 'required',
            'client_id' => 'nullable|string',
            'crop_id' => 'required|string',
            'requested_acreage' => 'required|string',
        ];
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'data' => $validator->errors(), 'statuscode' => '400']);
        }

        $data = $request->all();


        // Extract validated data
        $orderType = $data['order_type'];
        if (!empty($data['client_id'])) {
            $clientId = $data['client_id'];
        }

        $cropId = $data['crop_id'];
        $requestedAcreage = $data['requested_acreage'];
        $crop_price = "";

        // Get current date
        $currentDate = now()->format('Y-m-d');
        // return $currentDate;

        //    if($orderType == 1)
        //    {
        //     // return $currentDate;

        //         $applicableSchemes = Scheme::select('id','scheme_name','discount_price')->where('type', $orderType)
        //             ->where('crop_id', $cropId)
        //             ->where('period_from', '<=', $currentDate)
        //             ->where('period_to', '>=', $currentDate)
        //             ->where('min_acreage', '<=', $requestedAcreage)
        //             ->where('max_acreage', '>=', $requestedAcreage)
        //             ->where('status',1)
        //             ->get();

        //             return $applicableSchemes;



        //    }else 
        if ($orderType == 1) {

            // $applicableSchemes = Scheme::select('id','scheme_name','discount_price')->whereIn('type', [2, 3])
            // ->where('client_id',$clientId)
            // ->where('crop_id', $cropId)
            // ->where('period_from', '<=', $currentDate)
            // ->where('period_to', '>=', $currentDate)
            // ->where('min_acreage', '<=', $requestedAcreage)
            // ->where('max_acreage', '>=', $requestedAcreage)
            // ->where('status', 1)
            // ->get();
            // return $applicableSchemes;

            $applicableSchemes['schemes'] = Scheme::select('id', 'type', 'scheme_name', 'discount_price')->whereIn('type', [1, 2, 3])
                ->where(function ($query) use ($clientId) {
                    $query->where('client_id', $clientId)
                        ->orWhereNull('client_id')
                        ->orWhere('client_id', ''); // Add this condition
                })
                ->where('crop_id', $cropId)
                ->where('period_from', '<=', $currentDate)
                ->where('period_to', '>=', $currentDate)
                ->where('min_acreage', '<=', (int)$requestedAcreage)
                ->where('max_acreage', '>=', (int)$requestedAcreage)
                ->where('status', 1)
                ->get();
        } else if ($orderType == 4 || $orderType == 5) {
            $applicableSchemes['schemes'] = Scheme::select('id', 'type', 'crop_base_price', 'scheme_name', 'discount_price')->where('type', $orderType)
                ->where('client_id', $clientId)
                ->where('crop_id', $cropId)
                ->where('period_from', '<=', $currentDate)
                ->where('period_to', '>=', $currentDate)
                ->where('min_acreage', '<=', (int)$requestedAcreage)
                ->where('max_acreage', '>=', (int)$requestedAcreage)
                ->where('status', 1)
                ->get();
            // return $applicableSchemes;
        } else {
            return response()->json(['msg' => 'Applicable schemes not available', 'statuscode' => '200', 'status' => 'error']);
        }
        if (!empty($clientId) && $orderType == 1) {
            $get_client_details = RegionalClient::where('id', $clientId)->first();
            // return $get_client_details;
            $client_state = $get_client_details->state;
            $fetch_price = CropPrice::select('state_price')->where('crop_id', $data['crop_id'])->where('state', $client_state)->first();
            if (!empty($fetch_price) && $fetch_price['state_price'] != "") {

                $crop_base_price['crop_price'] = $fetch_price->state_price;
            } else {
                $fetch_price = Crop::select('base_price')->where('id', $data['crop_id'])->first();
                $crop_base_price['crop_price'] = $fetch_price->base_price;
            }
            // return $applicableSchemes;
            $applicableSchemes['crop_price']   = $crop_base_price['crop_price'];
        } else if ($orderType == 4 || $orderType == 5) {
            if (count($applicableSchemes['schemes']) != 0) {

                $applicableSchemes['crop_price'] = $applicableSchemes['schemes'][0]->crop_base_price;
            } else {
                $applicableSchemes['crop_price'] = '';
                return response()->json(['msg' => 'No schemes are available for the given type.Please create Scheme', 'statuscode' => '200', 'status' => 'success', 'data' => $applicableSchemes]);
            }
        }
        // return $applicableSchemes;
        if (isset($applicableSchemes) && count($applicableSchemes) == 0) {
            if (!empty($clientId)) {
                $get_client_details = RegionalClient::where('id', $clientId)->first();
                // return $get_client_details;
                $client_state = $get_client_details->state;
                $fetch_price = CropPrice::select('state_price')->where('crop_id', $data['crop_id'])->where('state', $client_state)->first();
                if (!empty($fetch_price) && !empty($fetch_price['state_price'])) {
                    $crop_base_price['crop_price'] = $fetch_price->state_price;
                } else {
                    $fetch_price = Crop::select('base_price')->where('id', $data['crop_id'])->first();
                    $crop_base_price['crop_price'] = $fetch_price->base_price;
                }
                return response()->json(['msg' => 'No schemes are available,Price Fetched Successfully', 'statuscode' => '200', 'status' => 'success', 'data' => $crop_base_price]);
            }
        } else {
            // return $applicableSchemes;
            return response()->json(['msg' => 'Applicable schemes found', 'statuscode' => '200', 'status' => 'success', 'data' => $applicableSchemes]);
        }
    }

    public function fetch_order_list()
    {

        $details = Auth::user();
        $get_user_data = User::where('id', $details->id)->first();
         if ($get_user_data->role == 'cso' )
         {
            $user_id =$get_user_data->id;
            $explode_client_ids = explode(',', $get_user_data->client_id);

            $services = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation'])
                ->whereIn('client_id', $explode_client_ids)
                ->whereHas('orderTimeline', function ($query) use ($user_id) {
                    $query->where('created_by_id', $user_id);
                })
                ->get();
            return response()->json(['data' => $services, 'msg' => 'Service List Fetched Successfully', 'statuscode' => '200', 'status' => 'success'], 200);

         }
       else if ($get_user_data->role == 'client') {
            $user_id = $get_user_data->id;
            $explode_client_ids = explode(',', $get_user_data->client_id);

            $services = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation'])
            ->whereIn('client_id', $explode_client_ids)
                // ->whereHas('orderTimeline', function ($query) use ($user_id) {
                //     $query->where('created_by_id', $user_id);
                // })
                ->get();
            return response()->json(['data' => $services, 'msg' => 'Service List Fetched Successfully', 'statuscode' => '200', 'status' => 'success'], 200);
        }
      else  if ($get_user_data->role == 'rtl') {
            $explode_client_ids = explode(',', $get_user_data->client_id);
            $services = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation'])->whereIn('client_id', $explode_client_ids)->get();
            return response()->json(['data' => $services, 'msg' => 'Service List Fetched Successfully', 'statuscode' => '200', 'status' => 'success'], 200);
        } else  if (
            $get_user_data->role == 'rm' || $get_user_data->role == 'accounts' || $get_user_data->role == 'hr'
            || $get_user_data->role == 'admin'
            || $get_user_data->role == 'super admin'
        ) {

            $services = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation'])->get();
            return response()->json(['data' => $services, 'msg' => 'Service List Fetched Successfully', 'statuscode' => '200', 'status' => 'success'], 200);
        } else {
            return response()->json(['data' => [], 'msg' => 'You do not have rights for this list', 'statuscode' => '200', 'status' => 'error'], 200);
        }

        // dd(DB::getQueryLog());
        // Transform the services to include battery IDs
        // $transformedServices = $services->map(function ($service) {
        //     return [

        //         'assetOperator' => $service->assetOperator,
        //         'asset' => $service->asset,
        //         // 'battery_ids' => $service->battery_ids,
        //         // 'batteries' => $service->batteries->toArray(),
        //         // Add other service details as needed
        //     ];
        // });

        // Return a JSON response
        return response()->json(['data' => $services, 'msg' => 'Service List Fetched Successfully', 'statuscode' => '200', 'status' => 'success'], 200);
    }

    public function fetch_single_order($id)
    {
        // Retrieve a specific scheme by ID
        $orders = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'orderTimeline', 'farmLocation'])->find($id);

        if (!$orders) {
            return response()->json(['msg' => 'Order not found', 'status' => 'error', 'statuscode' => '404']);
        }

        return response()->json(['data' => $orders]);
    }

    public function get_order_timeline($id)
    {
        $orders = Services::where('id',$id)->first();



        if (empty($orders)) {
            return response()->json(['msg' => 'Order not found', 'status' => 'error', 'statuscode' => '404']);
        }

        $order_timeline_id=$orders->order_details_id;

        $timeline_data=OrdersTimeline::where('id', $order_timeline_id)->get();
        if (empty($timeline_data)) {
            return response()->json(['msg' => 'Time Line not found', 'status' => 'error', 'statuscode' => '404']);
        }



        return response()->json(['msg' => 'Time Line Data fetched successfully..', 'statuscode'=>'200','data' => $timeline_data]);


    }

    public function fetch_assigned_details(Request $request)
    {

        $data = $request->all();
        $id = $data['id'];
        $check_service = Services::where('id', $id)->first();
        if (empty($check_service)) {
            return response()->json(['msg' => 'Service Does not exits', 'status' => 'success', 'statuscode' => '201', 'data' => []], 201);
        } else {
            // Retrieve asset details for a service
            $service = Services::with('asset')->find($id);
            return response()->json(['msg' => 'Services Fetched successfully', 'status' => 'success', 'statuscode' => '201', 'data' => $service], 201);

            // return $service;
        }
    }


    public function cancel_order(Request $request)
    {
        // Validation rules
        $rules = [
            'id' => 'required|exists:services,id',
            'cancel_remarks' => 'required|string|max:255',
        ];

        // Custom error messages
        $messages = [
            'id.required' => 'The service ID is required.',
            'id.exists' => 'The selected service does not exist.',
            'cancel_remarks.required' => 'The cancel remarks are required.',
            'cancel_remarks.string' => 'The cancel remarks must be a string.',
            'cancel_remarks.max' => 'The cancel remarks may not be greater than :max characters.',
        ];

        // Validate the incoming request
        $validator = Validator::make($request->all(), $rules, $messages);

        // Check if the validation fails
        if ($validator->fails()) {
            return response()->json([
                'msg' => 'Validation error',
                'status' => 'error',
                'statuscode' => '422',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Proceed with your logic if validation passes
        $data = $request->all();
        $id = $data['id'];
        $cancelRemarks = $data['cancel_remarks'];

        // Retrieve the service
        $service = Services::where('id', $id)->first();

        if (empty($service)) {
            return response()->json([
                'msg' => 'Service Does not exist',
                'status' => 'error',
                'statuscode' => '404',
                'data' => []
            ], 404);
        }

        // Update the service status and cancel remarks
        $update_service =   $service->update([
            'order_status' => 0,
            'cancel_remarks' => $cancelRemarks
        ]);

        if ($update_service) {

            $details = Auth::user();
            $timeline_data['cancel_created_by_id'] = $details->id;
            $timeline_data['cancel_created_by'] = $details->name;
            $timeline_data['cancel_date'] = date('Y-m-d');

            $update_timeline = OrdersTimeline::where('id', $service->order_details_id)->update($timeline_data);
        }
        if ($update_timeline) {
            return response()->json([
                'msg' => 'Service canceled successfully',
                'status' => 'success',
                'statuscode' => '200',
                'data' => $service
            ], 200);
        }
    }

    public function submit_assigned_operator(Request $request)
    {

        $data = $request->all();
        $id = $data['id'];
        $asset_operator_id = $data['asset_operator_id'];
        $store_data['asset_operator_id'] = $asset_operator_id;

        $check_service = Services::where('id', $id)->first();
        if (empty($check_service)) {
            return response()->json(['msg' => 'Service Does not exits', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            // Retrieve asset details for a service
            $get_asset_operator_details = AssetOperator::where('id', $asset_operator_id)->first();
            $store_data['asset_id'] = $get_asset_operator_details->asset_id;
            $get_asset_details = AssetDetails::where('id', $store_data['asset_id'])->first();
            // return $get_asset_operator_details;
            $store_data['battery_ids'] = $get_asset_details->battery_ids;
            if ($get_asset_details->assigned_status != 1) {
                return response()->json(['msg' => 'Service Cannot assigned, Allocation Asset is missing', 'status' => 'success', 'statuscode' => '200', 'data' => []]);
            }
            if (!empty($get_asset_details->battery_ids)) {
                $battery_ids = explode(',', $get_asset_details->battery_ids);
                foreach ($battery_ids as $battery_id) {
                    $check_battery = Battery::where('id', $battery_id)->first();
                    if ($check_battery->assigned_status != 1) {
                        return response()->json(['msg' => 'Service Cannot assigned, Allocation Battery is missing to Asset', 'status' => 'success', 'statuscode' => '200', 'data' => $check_battery]);
                    }
                }
            } else {
                return response()->json(['msg' => 'Service Cannot assigned, Battery ids missing in asset', 'status' => 'success', 'statuscode' => '200', 'data' => []]);
            }
            $store_data['assigned_date'] = date('Y-m-d');
            $store_data['order_status'] = 2;
            $store_data['assigned_status'] = 1;



            $service = Services::where('id', $id)->update($store_data);

            if ($service) {
                $assigned_operator_done =  AssetOperator::where('id', $asset_operator_id)->update(['assigned_status' => 1]);

                if ($assigned_operator_done) {

                    $details = Auth::user();
                    $order_timeline['assign_created_by_id'] = $details->id;
                    $order_timeline['assign_created_by'] = $details->name;
                    $order_timeline['assign_date'] =   date('Y-m-d');

                    $update_order_timeline = OrdersTimeline::where('id', $check_service->order_details_id)->update($order_timeline);
                }
            }

            return response()->json(['msg' => 'AssetOperator Assigned Successfully', 'status' => 'success', 'statuscode' => '201', 'data' => []], 201);

            // return $service;
        }
    }


    // check it later
    // public function storeOrUpdateAmountReceived(Request $request, $serviceId)
    // {
    //     // Retrieve the service
    //     $service = Service::findOrFail($serviceId);

    //     // Get the existing amount_received or initialize as an empty array if it doesn't exist
    //     $amountReceived = $service->amount_received ?? [];

    //     // Check if $amountReceived is an array and not empty
    //     if (is_array($amountReceived) && !empty($amountReceived)) {
    //         // Append the new data to the existing amount_received array
    //         $amountReceived[] = [
    //             'reference_no' => $request->input('reference_no'),
    //             'amount' => $request->input('amount'),
    //             'mode' => $request->input('mode')
    //         ];
    //     } else {
    //         // Initialize amount_received as an array with the new data
    //         $amountReceived = [
    //             [
    //                 'reference_no' => $request->input('reference_no'),
    //                 'amount' => $request->input('amount'),
    //                 'mode' => $request->input('mode')
    //             ]
    //         ];
    //     }

    //     // Update the amount_received field in the service
    //     $service->amount_received = $amountReceived;

    //     // Save the updated service
    //     $service->save();

    //     return response()->json(['message' => 'Amount received updated successfully', 'data' => $service]);
    // }

}
