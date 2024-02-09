<?php

namespace App\Http\Controllers;

use App\Models\Scheme;
use App\Models\Services;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;


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
            'scheme_ids' => 'required|string|max:100',
            'total_discount' => 'required|string',
            'extra_discount' => 'nullable|string',
            'remarks' => 'nullable|string',
            'amount_received' => 'nullable|string',
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

        // Query the database to get the latest farmer code for the state
        $latest_order_id= Services::select('order_id')
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
        $data['order_date']= date('d-m-Y');

        $service = Services::create($data);

        return response()->json(['msg' => 'Service created successfully','status'=>'success','statuscode' => '200', 'data' => $service]);
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
            return response()->json(['status'=>'error','data' => $validator->errors(),'statuscode'=>'400']);
        }

        $data = $request->all();
  

        // Extract validated data
        $orderType = $data['order_type'];
        if(!empty($data['client_id']))
        {
            $clientId = $data['client_id'];
        }
       
        $cropId = $data['crop_id'];
        $requestedAcreage = $data['requested_acreage'];

        // Get current date
        $currentDate = now()->format('d-m-Y');
        // return $currentDate;

       if($orderType == 1)
       {
        // return $currentDate;

            $applicableSchemes = Scheme::select('id','scheme_name','discount_price')->where('type', $orderType)
                ->where('crop_id', $cropId)
                ->where('period_from', '<=', $currentDate)
                ->where('period_to', '>=', $currentDate)
                ->where('min_acreage', '<=', $requestedAcreage)
                ->where('max_acreage', '>=', $requestedAcreage)
                ->where('status',1)
                ->get();

                return $applicableSchemes;
           


       }else if($orderType == 2 || $orderType == 3 )
       {
            $applicableSchemes = Scheme::select('id','scheme_name','discount_price')->whereIn('type', [1, 2, 3])
            ->where(function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->orWhereNull('client_id')
                    ->orWhere('client_id', ''); // Add this condition
            })
            ->where('crop_id', $cropId)
            ->where('period_from', '<=', $currentDate)
            ->where('period_to', '>=', $currentDate)
            ->where('min_acreage', '<=', $requestedAcreage)
            ->where('max_acreage', '>=', $requestedAcreage)
            ->where('status', 1)
            ->get();
       } else if ($orderType == 4 || $orderType == 5) {
            $applicableSchemes = Scheme::select('id','scheme_name','discount_price')->where('type', $orderType)
                ->where('client_id', $clientId)
                ->where('crop_id', $cropId)
                ->where('period_from', '<=', $currentDate)
                ->where('period_to', '>=', $currentDate)
                ->where('min_acreage', '<=', $requestedAcreage)
                ->where('max_acreage', '>=', $requestedAcreage)
                ->where('status', 1)
                ->get();
       }else{
        return response()->json(['msg' => 'Applicable schemes not available', 'statuscode' => '200','status' => 'error']);
       }
        // return $applicableSchemes;
        if (isset($applicableSchemes) && count($applicableSchemes) == 0)
       {
            return response()->json(['msg' => 'No schemes are available', 'statuscode' => '400', 'status' => 'error','data'=>""]);

       }else{
            return response()->json(['msg' => 'Applicable schemes found', 'statuscode' => '200', 'status' => 'success', 'data' => $applicableSchemes]);

       }
       

    }

    public function fetch_order_list()
    {
        // DB::enableQueryLog();
        // Fetch services with related information
        $services = Services::with(['assetOperator', 'asset'])->get();
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
        return response()->json(['services' => $services], 200);
    }

    public function fetch_single_order($id)
    {
        // Retrieve a specific scheme by ID
        $orders = Services::with(['assetOperator', 'asset'])->find($id);

        if (!$orders) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        return response()->json(['data' => $orders]);
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
