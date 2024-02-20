<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\AssetOperator;
use App\Models\Crop;
use App\Models\CropPrice;
use App\Models\OrdersTimeline;
use App\Models\RegionalClient;
use App\Models\Scheme;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;




class AssetOperatorController extends Controller
{
    //
    public function submit_operator_details(Request $request)
    {
        // Validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'rpc_no' => 'nullable|string|max:255',
            'dl_no' => 'nullable|string|max:255',
            'aadhaar_no' => 'nullable|string|max:255',
            'rpc_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'dl_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'aadhaar_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'start_date' => 'required|date',
            'user_id' => 'nullable|string|max:255',
            'user_password' => 'nullable|string|max:255',
            'vehicle_id' => 'nullable|string|max:255',
            'asset_id' => 'nullable|string|max:255',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // If validation passes, store the details
        $data = $validator->validated();



        $check_asset_id = AssetOperator::where('asset_id', $data['asset_id'])->first();

        if (!empty($check_asset_id)) {
            if ($check_asset_id->assigned_status) {
                $result_array = array(
                    'status' => 'error',
                    'statuscode' => '200',
                    'msg' => 'Asset already Assigned',
                    'data' => $check_asset_id
                );
                return response()->json($result_array, 200);
            }
        }
        $asset_id = $data['asset_id'];
        if (!empty($asset_id)) {
            $get_details = AssetDetails::where('id', $asset_id)->first();
            if (empty($get_details->battery_ids)) {
                return response()->json(['msg' => 'Battery not assigned to asset', 'status' => 'error', 'statuscode' => '200', 'data' => []]);
            }
        }

        $check_registered_user = User::where('login_id', $data['user_id'])->first();
        if (empty($check_registered_user)) {
            // Register a new user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['user_id'] . '@agriwings.in', // Ensure you have an 'email' field in your users table
                'login_id' => $data['user_id'],
                'text_password' => $data['user_password'],
                'password' => Hash::make($data['user_password']),
            ]);

            // Assign the 'operator' role to the new user
            $operatorRole = Role::where('name', 'operator')->first();
            $user->assignRole($operatorRole);
        } else {
            $result_array = array(
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Login Id already Exists',
                'data' => $check_registered_user
            );
            return response()->json($result_array, 200);
        }

        // remove user_id with saved user_id

        $details = Auth::user();

        $data['saved_by_name'] = $details->name;
        $data['saved_by_id'] = $details->id;
        $data['updated_by_name'] = "";
        $data['updated_by_id'] = "";




        $operator_code = AssetOperator::select('code')->latest('code')->first();
        // return $operator_code;

        $operator_code = json_decode(json_encode($operator_code), true);

        if (empty($operator_code) || $operator_code == null) {
            $initial_number = 1;
        } else {
            // Extract the numeric part and increment it
            $parts = explode('-', $operator_code['code']);
            $initial_number = (int)$parts[1] + 1;
        }

        // Format the code with leading zeros
        $next_operator_code = 'D-' . str_pad($initial_number, 5, '0', STR_PAD_LEFT);

        $data['code'] = $next_operator_code;


        // $data['user_id'] = $user->id;
        $data['vehicle_id'] = $data['vehicle_id'];

        $rpc_img = $request->file('rpc_img');
        if (!empty($rpc_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'DL_' . $randomString . '.' . $rpc_img->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $rpc_img->storeAs('rpc', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $data['rpc_img'] = $customFilename;
        }

        $dl_img = $request->file('dl_img');
        if (!empty($dl_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'DL_' . $randomString . '.' . $dl_img->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $dl_img->storeAs('dl', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $data['dl_img'] = $customFilename;
        }

        //s3 url
        //  https://agriwingsnew.s3.us-east-2.amazonaws.com/aadhar/
        $aadhaar_img = $request->file('aadhaar_img');
        if (!empty($aadhaar_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'Aadhar_' . $randomString . '.' . $aadhaar_img->getClientOriginalExtension();

            // Specify the filename when storing the file in S3
            $path = $aadhaar_img->storeAs('aadhar', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);
            // print_r($url);
            $data['aadhaar_img'] = $customFilename;
        }






        $assetOperator = AssetOperator::create($data);

        if ($assetOperator) {
            if (!empty($asset_id)) {
                AssetDetails::where('id', $asset_id)->update(['assigned_date' => date('Y-m-d'), 'assigned_status' => 1]);
            }
        }

        // You can return a response or perform any other logic here
        return response()->json(['msg' => 'Details stored successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $assetOperator]);
    }

    public function edit_operator_details(Request $request)
    {
        // Validation rules
        $rules = [
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:15',
            'rpc_no' => 'nullable|string|max:255',
            'dl_no' => 'nullable|string|max:255',
            'aadhaar_no' => 'nullable|string|max:255',
            'rpc_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'dl_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'aadhaar_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'start_date' => 'nullable|date',
            'user_id' => 'required|string|max:255',
            'user_password' => 'nullable|string|max:255',
            'vehicle_id' => 'nullable|string|max:255',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // If validation passes, store the details
        $data = $request->all();

        // return $data;
        $id = $data['id'];
        $assign_flag = 0;
        $remove_flag = 0;
        $asset_id = $data['asset_id'];


        if (!empty($data['asset_id'])) {
            $check_asset_id = AssetOperator::where('asset_id', $data['asset_id'])->first();


            if (!empty($check_asset_id)) {
                // return $check_asset_id->id;
                if ($check_asset_id->id != $id) {
                    $result_array = array(
                        'status' => 'error',
                        'statuscode' => '200',
                        'msg' => 'Asset already Assigned',
                        'data' => $check_asset_id
                    );
                    return response()->json($result_array, 200);
                }
            } else {

                $assign_flag = 1;
            }
        } else {
            $remove_flag = 1;
            $check_asset_id = AssetOperator::where('id', $data['id'])->first();
            $asset_id = $check_asset_id->asset_id;
        }

        $check_asset_operator = AssetOperator::where('id', $data['id'])->first();

        if ($data['end_date'] != "" && $check_asset_operator->assigned_status == 1) {
            $result_array = array(
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Asset already Assigned,User Block not possible',
                'data' => $check_asset_operator
            );
            return response()->json($result_array, 200);
        }

        // remove user_id with saved user_id

        $details = Auth::user();

        $data['updated_by_name'] = $details->name;
        $data['updated_by_id'] = $details->id;


        if (!empty($data['user_password'])) {
            User::where('login_id', $data['user_id'])->update(['text_password' => $data['user_password'], 'password' => Hash::make($data['user_password'])]);
        }


        $dl_img = $request->file('dl_img');
        if (!empty($dl_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'DL_' . $randomString . '.' . $dl_img->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $dl_img->storeAs('dl', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $data['dl_img'] = $customFilename;
        }

        //s3 url
        //  https://agriwingsnew.s3.us-east-2.amazonaws.com/aadhar/
        $aadhaar_img = $request->file('aadhaar_img');
        if (!empty($aadhaar_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'Aadhar_' . $randomString . '.' . $aadhaar_img->getClientOriginalExtension();

            // Specify the filename when storing the file in S3
            $path = $aadhaar_img->storeAs('aadhar', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);
            // print_r($url);
            $data['aadhaar_img'] = $customFilename;
        }

        $rpc_img = $request->file('rpc_img');
        if (!empty($rpc_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'DL_' . $randomString . '.' . $rpc_img->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $rpc_img->storeAs('rpc', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $data['rpc_img'] = $customFilename;
        }



        if ($data['end_date'] != "") {
            $data['status'] = 0;
        }



        // return [$remove_flag,$assign_flag];
        $assetOperator = AssetOperator::where('id', $data['id'])->update($data);

        if ($assetOperator) {
            if (empty($asset_id)) {
                AssetDetails::where('id', $asset_id)->update(['assigned_date' => null, 'assigned_status' => 0]);
            }
            if ($assign_flag) {
                if (!empty($asset_id)) {
                    AssetDetails::where('id', $asset_id)->update(['assigned_date' => date('Y-m-d'), 'assigned_status' => 1]);
                }
            }
            if ($remove_flag) {
                AssetDetails::where('id', $asset_id)->update(['assigned_date' => null, 'assigned_status' => 0]);
            }
        }

        $get_operator = AssetOperator::where('id', $data['id'])->get();



        // You can return a response or perform any other logic here
        return response()->json(['msg' => 'Details updated successfully', 'statuscode' => '200', 'status' => 'success', 'data' => $get_operator]);
    }

    public function delete_operator(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:asset_operators,id',
            ]);

            $id = $request->input('id');
            $end_date = $request->input('end_date');


            $assetOperator = AssetOperator::find($id);

            $asset_id = $assetOperator->asset_id;
            if ($assetOperator) {
                if (!empty($asset_id)) {
                    AssetOperator::where('id', $id)->update(['end_date' => $end_date, 'status' => 0, 'asset_id' => '']);
                    $assetOperator->delete();
                    AssetDetails::where('id', $asset_id)->update(['assigned_date' => null, 'assigned_status' => 0]);
                    return response()->json(['statuscode' => '200', 'status' => 'success',  'msg' => 'Record deleted successfully']);
                } else {
                    AssetOperator::where('id', $id)->update(['end_date' => $end_date, 'status' => 0]);
                    $assetOperator->delete();
                    return response()->json(['statuscode' => '200', 'status' => 'success',  'msg' => 'Record deleted successfully']);
                }
            } else {
                return response()->json(['statuscode' => '200', 'status' => 'success',  'msg' => 'Record not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['statuscode' => '400', 'status' => 'error',  'msg' => 'Error deleting record', 'data' => $e->getMessage()], 500);
        }
    }

    public function fetch_operators_to_assign()
    {
        $asset_operators = AssetOperator::select('id', 'code', 'name')->where('asset_id', '!=', '')->where('status', 1)->get();

        if (empty($asset_operators)) {
            return response()->json(['msg' => 'Asset Operator Does not exits to assign', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            // Retrieve asset details for a service
            return response()->json(['msg' => 'Asset Operator List Fetched successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $asset_operators], 201);
        }
    }

    public function get_all_operators()
    {
        $vehicle_list = AssetOperator::with('VehicleDetails', 'UserDetails')
            ->where('status', 1)
            ->get();

        if (!$vehicle_list->isEmpty()) {
            return ['data' => $vehicle_list, 'statuscode' => '200', 'status' => 'success', 'msg' => 'Operators list fetched successfully.'];
        } else {
            return ['status' => 'success', 'statuscode' => '200', 'msg' => 'Operators not found.'];
        }
    }

    public function get_operator_assigned_services()
    {
        $details = Auth::user();
        $id = $details->id;
        $get_user_id = User::where('id', $id)->first();
        $user_id = $get_user_id->login_id;
        // return $user_id;

        if (!empty($user_id)) {
            $fetch_operator_details = AssetOperator::where('user_id', $user_id)->first();
            // return [$fetch_operator_details, $get_user_id];
            $fetch_assigned_orders = Services::with('crop', 'farmerDetails', 'farmLocation')->where('asset_operator_id', $fetch_operator_details->id)->where('order_status', 2)->get();
            return response()->json(['msg' => 'Assigned Order List Fetched successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $fetch_assigned_orders], 201);
        } else {
            return response()->json(['msg' => 'You are not valid user for fetching this details', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        }
    }

    public function get_operator_accepted_services()
    {
        $details = Auth::user();
        $id = $details->id;
        $get_user_id = User::where('id', $id)->first();
        $user_id = $get_user_id->login_id;
        if (!empty($user_id)) {
            $fetch_operator_details = AssetOperator::where('user_id', $user_id)->first();
            $fetch_assigned_orders = Services::with('crop', 'farmerDetails', 'farmLocation')
            ->where('asset_operator_id', $fetch_operator_details->id)
            ->whereIn('order_status', [0, 3, 4, 5, 6])
            ->get();
            return response()->json(['msg' => 'Assigned Order List Fetched successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $fetch_assigned_orders], 201);
        } else {
            return response()->json(['msg' => 'You are not valid user for fetching this details', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        }
    }

    public function submit_operator_order_request(Request $request)
    {
        $data = $request->all();
        $id = $data['id'];
        $order_accepted = $data['order_accepted'];
        $details = Auth::user();
        $get_service_detail = Services::where('id', $id)->first();
        $asset_operator_id = $get_service_detail->asset_operator_id;
        $order_details_id = $get_service_detail->order_details_id;
        if (!$get_service_detail->assigned_status) {
            return response()->json(['msg' => 'Order not assigned yet', 'status' => 'error', 'statuscode' => '200', 'data' => []], 201);
        }
        if (!$order_accepted) {


            $update_service =  Services::where('id', $id)->update(['asset_operator_id' => null, 'assigned_status' => 0, 'assigned_date' => null, 'asset_id' => null, 'battery_ids' => null, 'order_accepted' => 0, 'order_status' => 1]);

            if ($update_service) {
                OrdersTimeline::where('id', $order_details_id)->update(['updated_by_id' => $details->id, 'updated_by' => $details->name]);
                AssetOperator::where('id', $asset_operator_id)->update(['assigned_status' => 0]);
            }
        } else {


            $update_service =  Services::where('id', $id)->update(['order_accepted' => 1, 'order_status' => 3]);

            if ($update_service) {
                OrdersTimeline::where('id', $order_details_id)->update(['aknowledged_created_by_id' => $details->id, 'aknowledged_created_by' => $details->name, 'aknowledged_date' => date('Y-m-d')]);
            }
        }
        // $details = Auth::user();
        // $id = $details->id;
        // $get_user_id = User::where('id', $id)->first();
        // $user_id = $get_user_id->login_id;
        // $fetch_operator_details = AssetOperator::where('user_id', $user_id)->first();
        // $fetch_assigned_orders = Services::with('crop')->where('asset_operator_id', $fetch_operator_details->id)->where('order_status', 2)->get();
        return response()->json(['msg' => 'Request Updated Successfully', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
    }

    public function start_spray(Request $request)
    {
        $data = $request->all();
        $validatedData = $request->validate([
            'id' => 'required|numeric',
            'chemical_used_ids' => 'required|string', // Assuming chemical_used_ids is a comma-separated string
            'farmer_available' => 'required|boolean',
            'fresh_water' => 'required|boolean',
            'noc_image' => 'image|mimes:jpeg,png,jpg,gif',
        ]);
        // return $data;
        $id = $data['id'];
        $check_order_exists = Services::where('id', $id)->first();
        // return $check_order_exists;
        if (empty($check_order_exists)) {
            return response()->json(['msg' => 'Service Does not exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            $noc_image = $request->file('noc_image');
            if (!empty($noc_image)) {
                // Generate a random string for the filename
                $randomString = Str::random(10); // Adjust the length as needed

                // Concatenate the random string with the desired file extension
                $customFilename = 'Noc_' . $randomString . '.' . $noc_image->getClientOriginalExtension();

                // Specify the filename when storing the file in S3
                $path = $noc_image->storeAs('noc_image', $customFilename, 's3');

                // Optionally, you can generate a publicly accessible URL
                $url = Storage::disk('s3')->url($path);
                // print_r($url);
                $data['noc_image'] = $customFilename;
            }
            $details = Auth::user();

            $data['spray_started_created_by_id'] = $details->id;
            $data['spray_started_created_by'] = $details->name;
            $data['spray_started_date'] =   date('Y-m-d');

            $update_services_done = Services::where('id', $id)->update(['spray_date' => date('Y-m-d'), 'spray_status' => 1, 'order_status' => 4]);
            if ($update_services_done) {
                $update_services = OrdersTimeline::where('id', $check_order_exists->order_details_id)->update($data);
            }

            if ($update_services) {
                return response()->json(['msg' => 'Spray Started Successfully..', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
            }
        }
    }

    public function complete_spray(Request $request)
    {
        $data = $request->all();

        // return $data;
        $id = $data['id'];
        $check_order_exists = Services::where('id', $id)->first();
        $currentDate = now()->format('Y-m-d');

        if (empty($check_order_exists)) {
            return response()->json(['msg' => 'Service Does not exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            if ($check_order_exists->requested_acreage != $data['sprayed_acreage']) {
                $clientId = $check_order_exists->client_id;
                $requestedAcreage = $data['sprayed_acreage'];
                $cropId   = $check_order_exists->crop_id;
                $total_discount_price = 0;
                $crop_base_price = 0;
                $total_discount = [];
                $client_discount = [];
                $agriwings_discount_price = 0;
                $agriwings_discount_price = 0;
                $total_amount = 0;
                $total_payable = 0;
                $scheme_ids_array = [];

                $orderType = $check_order_exists->order_type;
                if ($orderType == 1) {
                    $applicableSchemes = Scheme::select('id', 'type', 'client_id', 'scheme_name', 'discount_price')->whereIn('type', [1, 2, 3])
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

                    // return $applicableSchemes;

                    // start logic


                    // return $applicableSchemes;
                    if (count($applicableSchemes) != 0) {
                        // $explode_scheme_ids = explode(',', $data['scheme_ids']);
                        // return $explode_scheme_ids;


                        foreach ($applicableSchemes as $scheme) {
                            // $scheme = Scheme::find($scheme_id);

                            if ($scheme) {
                                $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                // $total_discount = $total_discount_price+$scheme->discount_price;
                                // return $scheme;
                                $scheme_ids_array[]  = $scheme->id;

                                if (!empty($scheme->client_id)) {
                                    // $crop_base_price = $scheme->crop_base_price;
                                    $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                } else {
                                    $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                }
                            }
                        }
                        $total_discount_sum = array_sum($total_discount);
                        // return $total_discount;
                        $total_discount_price = $total_discount_sum;
                        $total_client_discount  = array_sum($client_discount);
                    } else {
                        $total_discount_sum = 0;
                        $total_client_discount = 0;
                    }
                    // return $scheme_ids_array;


                    if (!empty($check_order_exists->client_id) && $orderType == 1) {
                        $get_client_details = RegionalClient::where('id', $check_order_exists->client_id)->first();
                        // return $get_client_details;
                        $client_state = $get_client_details->state;
                        $fetch_price = CropPrice::select('state_price')->where('crop_id', $check_order_exists->crop_id)->where('state', $client_state)->first();
                        //    return $client_state;
                        if (!empty($fetch_price)) {
                            $crop_base_price = $fetch_price->state_price;
                        } else {
                            $fetch_price = Crop::select('base_price')->where('id', $check_order_exists->crop_id)->first();
                            $crop_base_price = $fetch_price->base_price;
                        }
                    }

                    // $total_amount = $crop_base_price * $data['requested_acreage'];
                    //     // end logic
                }

                // check_again
                elseif ($orderType == 4 || $orderType == 5) {

                    $applicableSchemes = Scheme::select('id', 'type', 'crop_base_price', 'scheme_name', 'discount_price')->where('type', $orderType)
                        ->where('client_id', $clientId)
                        ->where('crop_id', $cropId)
                        ->where('period_from', '<=', $currentDate)
                        ->where('period_to', '>=', $currentDate)
                        ->where('min_acreage', '<=', (int)$requestedAcreage)
                        ->where('max_acreage', '>=', (int)$requestedAcreage)
                        ->where('status', 1)
                        ->get();

                    // return $applicableSchemes;


                    if (count($applicableSchemes) != 0) {
                        // $explode_scheme_ids = explode(',', $data['scheme_ids']);
                        // return $explode_scheme_ids;


                        foreach ($applicableSchemes as $scheme) {
                            // $scheme = Scheme::find($scheme_id);

                            if ($scheme) {
                                // return $scheme->discount_price;

                                $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                // $total_discount = $total_discount_price+$scheme->discount_price;
                                // return $total_discount;
                                $scheme_ids_array[]  = $scheme->id;
                                $crop_base_price = $scheme->crop_base_price;

                                if (!empty($scheme->client_id)) {
                                    // $crop_base_price = $scheme->crop_base_price;
                                    $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                } else {
                                    $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                }
                            }
                        }
                        $total_discount_sum = array_sum($total_discount);
                        // return $total_discount;
                        $total_discount_price = $total_discount_sum;
                        $total_client_discount  = array_sum($client_discount);
                    } else {
                        $total_discount_sum = 0;
                        $total_client_discount = 0;
                    }
                }

                // return "DS";


                if (isset($data['extra_discount'])) {
                    $total_discount_price = $total_discount_sum + (int)$data['extra_discount'];
                    $agriwings_discount = $agriwings_discount_price + (int)$data['extra_discount'];
                } else {
                    $agriwings_discount = $agriwings_discount_price;
                }
                // start new


                if (!empty($check_order_exists->extra_discount)) {
                    $total_discount_price = $total_discount_price + $check_order_exists->extra_discount;
                    $agriwings_discount_price = $agriwings_discount_price + $check_order_exists->extra_discount;
                }
                $total_amount = $crop_base_price * $data['sprayed_acreage'];
                $total_payable = $total_amount - $total_discount_price;
                // return [$total_amount, $total_discount_price];

                if ($total_discount_price > $total_amount) {
                    return response()->json(['msg' => 'Scheme Discount Price is increasing the Total Amount', 'status' => 'error', 'statuscode' => '200', 'data' => []], 201);
                }

                if ($total_payable > $total_amount) {
                    return response()->json(['msg' => 'Scheme Discount Price is increasing the Total Amount', 'status' => 'error', 'statuscode' => '200', 'data' => []], 201);
                }
                $scheme_ids = implode(',', $scheme_ids_array);

                $data['scheme_ids'] = $scheme_ids;
                $data['total_discount'] = $total_discount_price;
                $data['agriwings_discount'] = $agriwings_discount_price;
                $data['client_discount'] = $total_client_discount;
                $data['total_amount'] = $total_amount;
                $data['total_payable_amount'] = $total_payable;





                $amountReceivedString = $check_order_exists->amount_received;
                $amountReceivedArray = json_decode($amountReceivedString, true);
                $amount_receive_array = [];
                foreach ($amountReceivedArray as $amount_received) {
                    $amount_receive_array[] = $amount_received['amount'];
                }
                $amount_receive_sum = array_sum($amount_receive_array);
                // return $amount_receive_sum;

                if ($amount_receive_sum == 0) {
                    $data['added_amount'] = 0;
                    $data['refund_amount'] = 0;
                }
                // return $amount_receive_sum;

                if ($amount_receive_sum > $total_payable) {
                    $data['refund_amount'] = $amount_receive_sum - $total_payable;
                    $data['added_amount'] = 0;
                }

                if ($amount_receive_sum < $total_payable) {
                    $data['added_amount'] = $total_payable - $amount_receive_sum;
                    $data['refund_amount'] = 0;
                }

                // end new
            } else {
                $data['added_amount'] = 0;
                $data['refund_amount'] = 0;
            }
            // end


            // return [$data['added_amount'], $data['refund_amount'], $total_payable];
            // return [$crop_base_price, $total_discount_price, $agriwings_discount_price, $total_client_discount, $total_amount, $total_payable];

            $farmer_image = $request->file('farmer_image');
            if (!empty($farmer_image)) {
                // Generate a random string for the filename
                $randomString = Str::random(10); // Adjust the length as needed

                // Concatenate the random string with the desired file extension
                $customFilename = 'Farmer_img_' . $randomString . '.' . $farmer_image->getClientOriginalExtension();

                // Specify the filename when storing the file in S3
                $path = $farmer_image->storeAs('farmer_img_', $customFilename, 's3');

                // Optionally, you can generate a publicly accessible URL
                $url = Storage::disk('s3')->url($path);
                // print_r($url);
                $data['farmer_image'] = $customFilename;
            }
            $details = Auth::user();

            $timeline_data['farmer_image'] = $data['farmer_image'];
            $timeline_data['farmer_signature'] = $data['farmer_signature'];
            $timeline_data['updated_by'] = $details->name;
            $timeline_data['updated_by_id'] =  $details->id;


            unset($data['farmer_image']);
            unset($data['farmer_signature']);

            $data['order_status'] = 5;
            $data['spray_status'] = 2;
            $data['spray_date'] = date('Y-m-d');

            // $data['spray_started_created_by_id'] = $details->id;
            // $data['spray_started_created_by'] = $details->name;
            // $data['spray_started_date'] =   date('Y-m-d');

            $update_services_done = Services::where('id', $id)->update($data);
            // return $update_services_done;
            $update_services="";

            if ($update_services_done) {
                // return $check_order_exists->id;
                $update_services = OrdersTimeline::where('id', $check_order_exists->order_details_id)->update($timeline_data);
             
                $orders = Services::with(['assetOperator', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation', 'orderTimeline'])->find($id);

                if ($update_services) {
                    return response()->json(['msg' => 'Spray Completed Successfully..', 'status' => 'success', 'statuscode' => '200', 'data' => $orders], 201);
                }
            }

          
        }
    }

    public function mark_spray_successful(Request $request)
    {
        $data = $request->all();

        // return $data;
        $id = $data['id'];
        $check_order_exists = Services::where('id', $id)->first();
        // $currentDate = now()->format('Y-m-d');
         
        if (empty($check_order_exists) || $check_order_exists->order_status!=5) {
            return response()->json(['msg' => 'Service Does not exists or not in the spray complete status', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            // return $data['amount_received'];
            $amountReceivedString = $data['amount_received'];
            $amountReceivedArray = json_decode($amountReceivedString, true);
            $amount_receive_array = [];
            $refund_amount_array =[];
            foreach ($amountReceivedArray as $amount_received) {
                // return $amount_received;
                if($amount_received['mode'] == 1 || $amount_received['mode'] == 2 )
                {
                    $amount_receive_array[] = $amount_received['amount'];
                  

                }else  if($amount_received['mode'] == 3 || $amount_received['mode'] == 4 ){

                    $refund_amount_array[] = $amount_received['amount'];

                }
            }
         
            $amount_receive_sum = array_sum($amount_receive_array);

            if(!empty($refund_amount_array))
            {
                $refund_amount_sum = array_sum($refund_amount_array);
                $amount_receive_sum = $amount_receive_sum - $refund_amount_sum;
            }
               


            // return [$check_order_exists->total_payable_amount,$amount_receive_sum];
            if ($amount_receive_sum !=  $check_order_exists->total_payable_amount) {
                return response()->json(['msg' => 'Amount Received Sum is not equal to the total payable amount', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);

            }

            $details = Auth::user();
            $timeline_data['payment_received_created_by_id'] = $details->id;
            $timeline_data['payment_received_created_by'] = $details->name;
            $timeline_data['payment_received_date'] = date('Y-m-d');
            $timeline_data['delivered_created_by_id'] = $details->id;
            $timeline_data['delivered_created_by'] = $details->name;
            $timeline_data['delivered_date'] = date('Y-m-d');
            if (!empty($data['farmer_refund_signature'])) {
                $timeline_data['farmer_refund_signature'] = $data['farmer_refund_signature'];
            }

            $refund_image = $request->file('refund_image');
            if (!empty($refund_image)) {
                // Generate a random string for the filename
                $randomString = Str::random(10); // Adjust the length as needed

                // Concatenate the random string with the desired file extension
                $customFilename = 'Refund_img_' . $randomString . '.' . $refund_image->getClientOriginalExtension();

                // Specify the filename when storing the file in S3
                $path = $refund_image->storeAs('refund_img_', $customFilename, 's3');

                // Optionally, you can generate a publicly accessible URL
                $url = Storage::disk('s3')->url($path);
                // print_r($url);
                $timeline_data['refund_image'] = $customFilename;
            }
            $get_services_details = Services::find($id);

          

// return $timeline_data;

            $done_services =   Services::where('id', $id)->update(['amount_received' => $data['amount_received'], 'order_status'=>6,
                'payment_status'=>  1,'delivery_date'=>date('Y-m-d')]);

            if ($done_services) {
                $get_services_details = Services::find($id);
                $update_time_line = OrdersTimeline::where('id', $get_services_details->order_details_id)->update($timeline_data);
            }

            if ($update_time_line) {
                return response()->json(['msg' => 'Spray Makred Successful..', 'status' => 'success', 'statuscode' => '200', 'data' => $get_services_details], 201);
            }


            // refund_image
            //farmer_refund_signature

        }
    }
}
