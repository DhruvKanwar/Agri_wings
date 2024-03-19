<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\AssetOperator;
use App\Models\BaseClient;
use App\Models\Crop;
use App\Models\CropPrice;
use App\Models\FarmDetails;
use App\Models\OrdersTimeline;
use App\Models\RegionalClient;
use App\Models\Scheme;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PDF;
use App\Helpers\NumberToWords;




class AssetOperatorController extends Controller
{
    //

    private
        $stateArray = [
            "JK" => "JAMMU AND KASHMIR (UT)",
            "HP" => "HIMACHAL PRADESH",
            "PB" => "PUNJAB",
            "CH" => "CHANDIGARH (UT)",
            "UK" => "UTTARAKHAND",
            "HR" => "HARYANA",
            "DL" => "DELHI (UT)",
            "RJ" => "RAJASTHAN",
            "UP" => "UTTAR PRADESH",
            "BH" => "BIHAR",
            "SK" => "SIKKIM",
            "AR" => "ARUNACHAL PRADESH",
            "NL" => "NAGALAND",
            "MN" => "MANIPUR",
            "MZ" => "MIZORAM",
            "TR" => "TRIPURA",
            "ML" => "MEGHALAYA",
            "AS" => "ASSAM",
            "WB" => "WEST BENGAL",
            "JH" => "JHARKHAND",
            "OR" => "ODISHA",
            "CG" => "CHATTISGARH",
            "MP" => "MADHYA PRADESH",
            "GJ" => "GUJARAT",
            "DN" => "DADRA AND NAGAR HAVELI AND DAMAN AND DIU (UT)",
            "MH" => "MAHARASHTRA",
            "KA" => "KARNATAKA",
            "GA" => "GOA",
            "LD" => "LAKSHADWEEP (UT)",
            "KL" => "KERALA",
            "TN" => "TAMIL NADU",
            "PY" => "PUDUCHERRY (UT)",
            "AN" => "ANDAMAN AND NICOBAR ISLANDS (UT)",
            "TG" => "TELANGANA",
            "AP" => "ANDHRA PRADESH",
            "LA" => "LADAKH (UT)",
        ];


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

        $check_mobile_no = AssetOperator::where('phone', $data['phone'])->first();

        if (!empty($check_mobile_no) && $check_mobile_no->phone == $data['phone'] ) {
            $result_array = array(
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Mobile No already Exists',
                'data' => $check_asset_id->phone
            );
            return response()->json($result_array, 200);
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
                'role' => 'operator'
            ]);

            // Assign the 'operator' role to the new user
            // $operatorRole = Role::where('name', 'operator')->first();
            // $user->assignRole($data['role']);
            $user->assignRole('operator');
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




        $operator_code = AssetOperator::withTrashed()->select('code')->latest('code')->first();
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
            $update_vehicle = Vehicle::where('id', $data['vehicle_id'])->update(['operator_id' => $assetOperator->id]);
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
            $update_vehicle = Vehicle::where('id', $data['vehicle_id'])->update(['operator_id' => $data['id']]);
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

            $data = $request->all();
            $id = $request->input('id');
            $end_date = $request->input('end_date');


            $assetOperator = AssetOperator::find($id);

            // start

            $check_asset_operator = AssetOperator::where('id', $data['id'])->first();

            if ($data['end_date'] != "" && $check_asset_operator->assigned_status == 1) {
                $result_array = array(
                    'status' => 'error',
                    'statuscode' => '200',
                    'msg' => 'Order already Assigned,User Block not possible',
                    'data' => $check_asset_operator
                );
                return response()->json($result_array, 200);
            }

            $check_services_table = Services::where('asset_operator_id', $data['id'])
                ->whereIn('order_status', [1, 2, 3, 4, 5])
                ->first();



            if ($data['end_date'] != "" && !empty($check_services_table)) {
                $result_array = array(
                    'status' => 'error',
                    'statuscode' => '200',
                    'msg' => 'Order Already Assigned to  this operator. We can not make this user Inactive',
                    'data' => $check_services_table
                );
                return response()->json($result_array, 200);
            }

            // end

            $asset_id = $assetOperator->asset_id;
            $vehicle_id = $assetOperator->vehicle_id;

            if ($assetOperator) {
                if (!empty($asset_id)) {
                    AssetOperator::where('id', $id)->update(['end_date' => $end_date, 'status' => 0, 'asset_id' => '','vehicle_id'=>'']);
                    User::where('login_id', $assetOperator->user_id)->update(['status'=>0]);
                    if(!empty($vehicle_id))
                    {
                        Vehicle::where('id',$vehicle_id)->update(['operator_id'=>'']);
                    }
                    $assetOperator->delete();
                    AssetDetails::where('id', $asset_id)->update(['assigned_date' => null, 'assigned_status' => 0]);
                    return response()->json(['statuscode' => '200', 'status' => 'success',  'msg' => 'Record deleted successfully']);
                } else {
                    AssetOperator::where('id', $id)->update(['end_date' => $end_date, 'status' => 0, 'vehicle_id' => '']);
                    User::where('login_id', $assetOperator->user_id)->update(['status' => 0]);
                    if (!empty($vehicle_id)) {
                        Vehicle::where('id', $vehicle_id)->update(['operator_id' => '']);
                    }
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
        $asset_operators = AssetOperator::select('id', 'phone', 'code', 'name')->where('asset_id', '!=', '')->where('status', 1)->get();

        if (empty($asset_operators)) {
            return response()->json(['msg' => 'Asset Operator Does not exits to assign', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            // Retrieve asset details for a service
            return response()->json(['msg' => 'Asset Operator List Fetched successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $asset_operators], 201);
        }
    }

    public function get_all_operators()
    {
        $vehicle_list = AssetOperator::with('VehicleDetails', 'UserDetails')->withTrashed()->get();

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

            $update_services_done = Services::where('id', $id)->update(['spray_status' => 1, 'order_status' => 4]);

            if ($update_services_done) {
                $update_location_coordinates = DB::table('farm_details')
                    ->where(
                        'id',
                        $check_order_exists->farm_location
                    )
                    ->update(['location_coordinates' => $data['location_coordinates']]);

                unset($data['location_coordinates']);
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
        // $currentDate = now()->format('Y-m-d');

        $orderDate = $check_order_exists->order_date;

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
                $type_1 = 0;
                $type_2 = 0;
                $type_3 = 0;
                $type_4 = 0;
                // $type_5 = 0;
                $type_inactive_1 = 0;
                $type_inactive_2 = 0;
                $type_inactive_3 = 0;
                $type_inactive_4 = 0;

                $orderType = $check_order_exists->order_type;

                if ($orderType == 1) {
                    $applicableSchemes = Scheme::withTrashed()
                        ->select(
                            'id',
                            'type',
                            'client_id',
                            'scheme_name',
                            'discount_price',
                            'status',
                            'deleted_at'
                        )
                        ->whereIn('type', [1, 2, 3])
                        ->where(function ($query) use ($clientId) {
                            $query->where('client_id', $clientId)
                                ->orWhereNull('client_id')
                                ->orWhere('client_id', ''); // Add this condition
                        })
                        ->where('crop_id', $cropId)
                        ->where('period_from', '<=', $orderDate)
                        ->where('period_to', '>=', $orderDate)
                        ->where('min_acreage', '<=', (int)$requestedAcreage)
                        ->where('max_acreage', '>=', (int)$requestedAcreage)
                        ->orderBy('status', 'desc') // Sort by status in descending order
                        ->orderBy('updated_at', 'desc') // Then sort by updated_at in descending order
                        ->get()
                        ->sortByDesc(function ($scheme) {
                            return $scheme->status . '-' . $scheme->updated_at;
                        });


                    // return $applicableSchemes;

                    // start logic


                    if (count($applicableSchemes) != 0) {
                        // $explode_scheme_ids = explode(',', $data['scheme_ids']);
                        // return $explode_scheme_ids;

                        // return $applicableSchemes;
                        foreach ($applicableSchemes as $scheme) {
                            // $scheme = Scheme::find($scheme_id);

                            if ($scheme) {
                                if ($scheme->status && $scheme->type == 1) {
                                    $type_1 = 1;
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
                                if (!($scheme->status) && $scheme->type == 1 && !$type_1 && !$type_inactive_1) {
                                    if (date('Y-m-d', strtotime($scheme->deleted_at)) >= $orderDate) {
                                        // return 'in';
                                        $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        $scheme_ids_array[]  = $scheme->id;
                                        $type_inactive_1 = 1;
                                        if (!empty($scheme->client_id)) {
                                            // $crop_base_price = $scheme->crop_base_price;
                                            $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        } else {
                                            $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                        }
                                    }
                                }
                                // return "Ew";
                                //
                                if ($scheme->status && $scheme->type == 2) {
                                    $type_2 = 1;
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
                                if (!($scheme->status) && $scheme->type == 2 && !$type_2 && !$type_inactive_2) {
                                    if (date('Y-m-d', strtotime($scheme->deleted_at)) >= $orderDate) {
                                        $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        $scheme_ids_array[]  = $scheme->id;
                                        $type_inactive_2 = 1;
                                        if (!empty($scheme->client_id)) {
                                            // $crop_base_price = $scheme->crop_base_price;
                                            $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        } else {
                                            $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                        }
                                    }
                                }
                                //
                                //
                                if ($scheme->status && $scheme->type == 3) {
                                    $type_3 = 1;
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
                                if (!($scheme->status) && $scheme->type == 3 && !$type_3 && !$type_inactive_3) {
                                    if (date('Y-m-d', strtotime($scheme->deleted_at)) >= $orderDate) {
                                        $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        $scheme_ids_array[]  = $scheme->id;
                                        $type_inactive_3 = 1;
                                        if (!empty($scheme->client_id)) {
                                            // $crop_base_price = $scheme->crop_base_price;
                                            $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        } else {
                                            $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                        }
                                    }
                                }
                                //
                            }
                        }
                        // return $total_discount;
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

                    // $applicableSchemes = Scheme::withTrashed()->select('id', 'type', 'crop_base_price', 'scheme_name', 'discount_price')->where('type', $orderType)
                    //     ->where('client_id', $clientId)
                    //     ->where('crop_id', $cropId)
                    //     ->where('period_from', '<=', $orderDate)
                    //     ->where('period_to', '>=', $orderDate)
                    //     ->where('min_acreage', '<=', (int)$requestedAcreage)
                    //     ->where('max_acreage', '>=', (int)$requestedAcreage)
                    //     // ->orderBy('id','desc')
                    //     // ->where('status', 1)
                    //     ->first();

                    $applicableSchemes = Scheme::withTrashed()
                        ->select('id', 'type', 'crop_base_price', 'scheme_name', 'discount_price', 'status', 'deleted_at')->where('type', $orderType)
                        ->where('client_id', $clientId)
                        ->where('crop_id', $cropId)
                        ->where('period_from', '<=', $orderDate)
                        ->where('period_to', '>=', $orderDate)
                        ->where('min_acreage', '<=', (int)$requestedAcreage)
                        ->where('max_acreage', '>=', (int)$requestedAcreage)
                        ->orderBy('status', 'desc') // Sort by status in descending order
                        ->orderBy('updated_at', 'desc') // Then sort by updated_at in descending order
                        ->get()
                        ->sortByDesc(function ($scheme) {
                            return $scheme->status . '-' . $scheme->updated_at;
                        });


                    // return $applicableSchemes;


                    if (count($applicableSchemes) != 0) {
                        // $explode_scheme_ids = explode(',', $data['scheme_ids']);
                        // return $explode_scheme_ids;


                        foreach ($applicableSchemes as $scheme) {
                            // $scheme = Scheme::find($scheme_id);

                            if ($scheme) {
                                // return $scheme->discount_price;
                                if ($scheme->status) {
                                    $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                    // $total_discount = $total_discount_price+$scheme->discount_price;
                                    // return $total_discount;

                                    $crop_base_price = $scheme->crop_base_price;
                                    $scheme_ids_array[]  = $scheme->id;
                                    $type_4 = 1;
                                    if (!empty($scheme->client_id)) {
                                        // $crop_base_price = $scheme->crop_base_price;

                                        $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                    } else {

                                        $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                    }
                                }
                                if (!($scheme->status) && !$type_4 && !$type_inactive_4) {
                                    if (date('Y-m-d', strtotime($scheme->deleted_at)) >= $orderDate) {
                                        $total_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        $scheme_ids_array[]  = $scheme->id;
                                        $type_inactive_4 = 1;
                                        if (!empty($scheme->client_id)) {
                                            // $crop_base_price = $scheme->crop_base_price;
                                            $client_discount[] = $data['sprayed_acreage'] * $scheme->discount_price;
                                        } else {
                                            $agriwings_discount_price = $data['sprayed_acreage'] * $scheme->discount_price;
                                        }
                                    }
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
            $timeline_data['completed_date'] =  date('Y-m-d');



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
            $update_services = "";
            $update_services = OrdersTimeline::where('id', $check_order_exists->order_details_id)->update($timeline_data);

            $orders = Services::with(['assetOperator', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation', 'orderTimeline'])->find($id);
            return response()->json(['msg' => 'Spray Completed Successfully..', 'status' => 'success', 'statuscode' => '200', 'data' => $orders], 201);
           
            // if ($update_services_done) {
            //     // return $check_order_exists->id;
              

            //     if ($update_services) {
            //     }
            // }
        }
    }

    public function mark_spray_successful(Request $request)
    {
        $data = $request->all();
        DB::beginTransaction();
        // return $data;
        $id = $data['id'];
        $check_order_exists = Services::where('id', $id)->first();
        // $currentDate = now()->format('Y-m-d');

        if (empty($check_order_exists) || $check_order_exists->order_status != 5) {
            return response()->json(['msg' => 'Service Does not exists or not in the spray complete status', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        } else {
            // return $data['amount_received'];

            $details = Auth::user();
            $timeline_data['payment_received_created_by_id'] = $details->id;
            $timeline_data['payment_received_created_by'] = $details->name;
            $timeline_data['payment_received_date'] = date('Y-m-d');
            $timeline_data['delivered_created_by_id'] = $details->id;
            $timeline_data['delivered_created_by'] = $details->name;
            $timeline_data['delivered_date'] = date('Y-m-d');

         

            try {
                if ($check_order_exists->order_type == 4 || $check_order_exists->order_type == 5) {
                    $done_services =   Services::where('id', $id)->update([
                        'order_status' => 6,
                        'payment_status' =>  1, 'delivery_date' => date('Y-m-d')
                    ]);

                    if ($done_services) {
                        $get_services_details = Services::find($id);
                        $update_time_line = OrdersTimeline::where('id', $get_services_details->order_details_id)->update($timeline_data);
                    }

                    if ($update_time_line) {
                        // Commit the transaction
                        DB::commit();
                        self::send_invoice_sms($get_services_details->id);

                        return response()->json(['msg' => 'Spray Marked Successful..', 'status' => 'success', 'statuscode' => '200', 'data' => $get_services_details], 201);
                    }
                }
            } catch (\Exception $e) {
                // If an error occurs, rollback the transaction
                DB::rollback();

                // Return an error response
                return response()->json(['msg' => 'An error occurred while marking spray.', 'status' => 'error', 'statuscode' => '500', 'error' => $e->getMessage()], 500);
            }

            $amountReceivedString = $data['amount_received'];
            $amountReceivedArray = json_decode($amountReceivedString, true);
            $amount_receive_array = [];
            $refund_amount_array = [];
            foreach ($amountReceivedArray as $amount_received) {
                // return $amount_received;
                if ($amount_received['mode'] == 1 || $amount_received['mode'] == 2) {
                    $amount_receive_array[] = $amount_received['amount'];
                } else  if ($amount_received['mode'] == 3 || $amount_received['mode'] == 4) {

                    $refund_amount_array[] = $amount_received['amount'];
                }
            }

            $amount_receive_sum = array_sum($amount_receive_array);

            if (!empty($refund_amount_array)) {
                $refund_amount_sum = array_sum($refund_amount_array);
                $amount_receive_sum = $amount_receive_sum - $refund_amount_sum;
            }



            // return [$check_order_exists->total_payable_amount,$amount_receive_sum, number_format($amount_receive_sum, 3)];
            if (number_format($amount_receive_sum, 3) != number_format($check_order_exists->total_payable_amount, 3)) {
                return response()->json(['msg' => 'Amount Received Sum is not equal to the total payable amount', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
            }


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
            $get_services_details = Services::with('farmLocation')->find($id);


            $farm_state = $get_services_details->farmLocation->state;
            // return $timeline_data;

            // start
            $stateName = strtoupper($farm_state);
            $stateCode = $this->generateStateCode($stateName);


            // Query the database to get the latest farmer code for the state
            $latestCode = Services::where('invoice_no', 'like', "AW$stateCode%")
                ->orderBy('invoice_no', 'desc')
                ->value('invoice_no');

            // Generate the new farmer code
            if ($latestCode) {
                $generated_invoice_no = $this->generateInvoiceNoFromLatest($stateCode, $latestCode);
            } else {
                $generated_invoice_no = "AW$stateCode-24000001";
            }
            // end

    

            try {
                $done_services = Services::where('id', $id)->update([
                    'amount_received' => $data['amount_received'],
                    'order_status' => 6,
                    'payment_status' => 1,
                    'delivery_date' => date('Y-m-d'),
                    'invoice_no' => $generated_invoice_no
                ]);

                if ($done_services) {
                    $get_services_details = Services::find($id);
                    $update_time_line = OrdersTimeline::where('id', $get_services_details->order_details_id)->update($timeline_data);
                }

                if ($update_time_line) {
               
                    $fetch_services = Services::where('id', $id)->first();
                    AssetOperator::where('id', $fetch_services->asset_operator_id)->update(['assigned_status' => 0]);

                    // Commit the transaction
                    DB::commit();
                    self::send_invoice_sms($get_services_details->id);
                    return response()->json(['msg' => 'Spray Marked Successful..', 'status' => 'success', 'statuscode' => '200', 'data' => $get_services_details], 201);
                }
            } catch (\Exception $e) {
                // If an error occurs, rollback the transaction
                DB::rollback();

                // Return an error response
                return response()->json(['msg' => 'An error occurred while marking spray.', 'status' => 'error', 'statuscode' => '500', 'error' => $e->getMessage()], 200);
            }


            // refund_image
            //farmer_refund_signature

        }
    }

    private function generateInvoiceNoFromLatest($stateCode, $latestCode)
    {
        // Extract the numeric part and increment
        // $numericPart = (int)substr($latestCode, -4) + 1;
        $numericPart = (int)substr($latestCode, strpos($latestCode, '-') + 1);
        // Generate the new farmer code
        $numericPart += 1;

        // Generate the new farmer code
        $newCode = "AW" . $stateCode . "-" . str_pad($numericPart, 4, '0', STR_PAD_LEFT);

        return $newCode;
    }

    public function generate_invoice_pdf_old($id)
    {
        // return 1;
        $data = [
            'invoice_number' => 'INV-123',
            'customer_name' => 'John Doe',
            'id' => $id
            // Add more data as needed
        ];
        $pdf = PDF::loadView('invoicePDF.invoice', $data);
        return $pdf->stream('sampleTest.pdf');
        // return $pdf->download('sampleTest.pdf');
    }
    public static  function generate_invoice_pdf($str)
    {
        $id = base64_decode($str);
        // $id = base64_encode($str);

        // return $id;
        $order_details = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation'])->where('id', $id)->first();

        if (empty($order_details)) {
            return "Invalid Invoice Number";
        }

        // return $order_details->clientDetails;
        $base_client_id = $order_details->clientDetails->base_client_id;
        $get_base_client_details = BaseClient::where('id', $base_client_id)->first();


        $s3_url = 'https://agriwingsnew.s3.us-east-2.amazonaws.com';
        // echo "<pre>"; print_r($order_details['Orderactivity']['CropDetail']['crop_price']); die;
        // $fmc_img = public_path('assets/fmc.jpg');
        $auth_sign = public_path('assets/gv_sign.jpg');
        $footer_img = public_path('assets/footer_pdf.png');
        $save_water = @$order_details['Orderactivity']['CropName']['water_qty'] * @$order_details['total_acerage'];
        // dd($order_details['delivery_date']);
        // $fmc_img = public_path('assets/fmc.jpg');
        //
        $d2f_img  = public_path('assets/d2f.png');



        if (!empty($get_base_client_details->logo_img)) {
            $comp_logo = $s3_url . '/logo_img/' . $get_base_client_details->logo_img;
        } else {
            $comp_logo =   public_path('assets/agriwing.png');;
        }

        // ===sign image
        if (!empty($get_base_client_details->sign_img)) {
            $auth_sign = $s3_url . '/sign_img/' . $get_base_client_details->sign_img;
        } else {
            $auth_sign = '';
        }
        //sign name
        if (!empty($get_base_client_details->signature_name)) {
            $sign_name = $get_base_client_details->signature_name;
        } else {
            $sign_name = '';
        }

        // . $farm_state . '(' . $state_data->state_code . ')

        $html = '<!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <title>Invoice</title>

                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        font-family: "Helvetica Neue", "Helvetica", "Arial", sans-serif;
                    }
                    body {
                        width: 100%;
                    }

                    body .p1 {
                        padding: 0.5rem;
                    }
                    body .textRight {
                        text-align: right;
                    }
                    body .textLeft {
                        text-align: left;
                    }
                    body .textCenter {
                        text-align: center;
                    }
                    body .size1 {
                        font-size: 14px;
                        line-height: 20px;
                    }
                    body .size2 {
                        font-size: 18px;
                        line-height: 28px;
                    }
                    body .size3 {
                        font-size: 22px;
                        line-height: 38px;
                    }
                    body .bold {
                        font-weight: bold;
                    }
                    body .redBg {
                        background: #355a2e;
                        padding: 14px;
                    }
                    body td,
                    body th {
                        vertical-align: middle;
                    }
                    body .headBg {
                        min-width: 60px;
                        -webkit-clip-path: polygon(0% 0%, 0% 100%, 100% 100%, 60px 0%);
                        clip-path: polygon(0% 0%, 0% 100%, 100% 100%, 60px 0%);
                    }
                    body .headTitle {
                        width: 100%;
                        -webkit-clip-path: polygon(0% 0%, 30px 100%, 100% 100%, 100% 0%);
                        clip-path: polygon(0% 0%, 30px 100%, 100% 100%, 100% 0%);
                    }
                    body .headTitle .title {
                        padding: 10px 32px 10px 10px;
                        color: #fff;
                        font-size: 24px;
                        font-weight: 500;
                    }
                    body .inlineDetail a {
                        min-width: 70px;
                    }
                    body .inlineDetail a.address {
                        max-width: 10ch;
                    }
                    body .billingClient {
                        border-radius: 20px;
                        border: 1px solid rgba(131, 131, 131, 0.5019607843);
                        padding: 4px;
                    }
                    body .billingClient .details {
                        margin-top: 6px;
                        background: #ededed;
                        border-radius: 12px;
                        padding: 12px;
                    }
                    body .key {
                        width: 25%;
                    }
                    body .value {
                        font-weight: 500;
                    }
                    body .value p {
                        max-width: 30ch;
                    }
                    body .invTable {
                        border-radius: 20px;
                        border: 1px solid rgba(131, 131, 131, 0.5019607843);
                        padding: 6px 8px 4px;
                    }
                    body .invTable td {
                        padding-bottom: 4px;
                    }
                    body .serviceDetailsTable {
                        width: 80%;
                        margin: 1rem auto;
                        border-collapse: collapse;
                    }
                    body .serviceDetailsTable thead {
                        border-top: 2px solid;
                        border-bottom: 2px solid;
                    }
                    body .serviceDetailsTable thead th {
                        padding: 5px 16px;
                    }
                    body .serviceDetailsTable td {
                        padding: 4px 16px;
                        line-height: 26px;
                    }
                    body .serviceDetailsTable td svg {
                        height: 16px;
                        width: 16px;
                        margin-right: 12px;
                        margin-bottom: -4px;
                    }

                    body .verticalTop {
                        vertical-align: top;
                    }
                    body .innerTable td {
                        padding: 0;
                        line-height: 20px;
                        font-size: 14px;
                    }
                    body .p-0 {
                        padding: 0 10px !important;
                    }
                    body .size4 {
                        font-size: 18px;
                    }

                    body .pdfHeading {
                        padding-left: 12px;
                        text-decoration: underline;
                    }
                    body .footer {
                        position: fixed;
                        bottom: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        width: 90%;
                    }
                    body ul {
                        width: 90%;
                        margin: auto;
                        font-size: 8px;
                        padding-top: 1rem;
                    }
                    body .companyName {
                        font-size: 22px;
                        text-transform: capitalize;
                        margin-bottom: 8px;
                    }
                    body .companyAddress {
                        font-size: 12px;
                        margin: auto;
                        text-transform: capitalize;
                        color: #333; white-space: normal;
                        width: 450px;
                    }
                    body .companyContact {
                        font-size: 12px;
                        color: #333;
                        margin: 0.2rem auto 3rem;
                    }
                    body .footerAdrress {
                        background-color: #355a2e;
                        color: #fff;
                        text-align: center;
                        padding: 8px;
                        margin-top: 4px;
                        font-size: 14px;
                    }
                    tr.head td {
                        border-top: 2px solid;
                        border-bottom: 2px solid;
                        padding: 4px;
                    }
                </style>
            </head>
            <body>
                <table style="width: 100%; margin-top: 30px">
                    <tr>
                        <td class="textRight" style="width: 160px;">
                            <img
                                src="' . $comp_logo . '"
                                alt="logo"
                                style="height: 40px; object-fit: contain; margin-left: 16px;"
                            />
                        </td>

                        <td style="vertical-align: bottom; text-align: center; width: 100%">
                            <p
                                class="textCenter"
                                style="
                                    font-size: 16px;
                                    text-decoration: underline;
                                    margin-left: -20px;
                                "
                            >
                                <strong>Bill of Supply</strong>
                            </p>
                        </td>

                        <td class="textLeft" style="width: 160px;">
                            <img
                                src="' . $d2f_img . '"
                                alt="logo"
                                style="width: 100px; object-fit: contain"
                            />
                        </td>
                    </tr>
                </table>

                <table style="width: 90%; margin: 1rem auto">';
        $company_name = $get_base_client_details->client_name;
        $firstLetterCompany = substr($company_name, 0, 1);


        if (!empty($order_details->invoice_no)) {
            $invoice_no = explode('-', $order_details->invoice_no);
            $invoice_no_generate = $invoice_no[0] . '' . $invoice_no[1];
        }

        // echo "<pre>";
        // print_r($order_details);
        // exit;

        $farm_state = $order_details->farmLocation->state;
        // $company_state =   $order_details->clientDetails->state;

        // $gst_address =  $order_details->clientDetails->address;

    

        // $state_data = StateMaster::where('state_name', $farm_state)->first();

        $html .= '<tbody>
                        <tr>
                            <td colspan="2">
                                <p class="companyName textCenter">
                                    <strong>D2F Services Private Limited</strong>
                                </p>
                                <p class="companyAddress textCenter" style="max-width: 60ch">
                                B-103, Bestech Business Towers, Mohali – 160062
                                </p>
                                <p class="companyContact textCenter">
                                PAN: AAJCD8838K &nbsp;&nbsp;|&nbsp;&nbsp; GSTIN: 03AAJCD8838K1Z9
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 60%">
                                <p class="pdfHeading">Billed to (Farmer) :</p>
                                <table style="width: 90%; margin: 0.5rem auto">
                                    <tr>
                                        <td class="size1">' . $order_details->farmerDetails->farmer_name . '</td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: top" class="size1">
                                        +91 ' . $order_details->farmerDetails->farmer_mobile_no  . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="size1">
                                            <p>
                                            #' . @$order_details->farmLocation->address . ',<br /> ' . @$order_details->farmLocation->district . ',<br />' . @$order_details->farmLocation->state . '
                                            </p>
                                        </td>
                                    </tr>
                                    <!-- <tr>
                                                 <td class="size1">State Code: 06</td>
                                         </tr> -->
                                </table>
                            </td>
                            <td style="width: 360px">
                                <table class="billingClient" style="width: 90%">
                                    <tr>
                                        <td class="textCenter">
                                            <div class="size1">
                                                <strong>Invoice Details</strong>
                                            </div>
                                            <div class="details">
                                                <p class="inlineDetail textLeft size1">
                                                    <a>
                                                        Invoice No.
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;
                                                    </a>
                                                    ' . @$invoice_no_generate . '
                                                </p>
                                                <p class="inlineDetail textLeft size1">
                                                    <a>
                                                        Invoice Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;
                                                    </a>
                                                    ' . date('d-m-Y', strtotime(@$order_details->delivery_date)) . '
                                                </p>
                                                <p class="inlineDetail textLeft size1">
                                                    <a>
                                                        State
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        &nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;
                                                    </a>
                                                    ' . $farm_state . '
                                                </p>
                                                <p class="inlineDetail textLeft size1">
                                                <a>Place of Supply :&nbsp;&nbsp;</a>&nbsp;&nbsp;' . $farm_state . '

                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <table
                                    style="width: 100%; border-collapse: collapse; margin-top: 1.5rem"
                                >
                                    <tr class="head">
                                        <td class="size1"><strong>Service Details</strong></td>
                                        <td class="textRight size1" style="white-space: nowrap">
                                            <strong>Basic Price</strong>
                                        </td>
                                        <td class="textRight size1" style="white-space: nowrap">
                                            <strong>Acreage</strong>
                                        </td>
                                        <td class="textRight size1" style="white-space: nowrap">
                                            <strong>Total Amount</strong>
                                        </td>
                                    </tr>';

        $get_crop_details=Crop::where('id', $order_details->crop_id)->first();

        $water_saved_qty= $get_crop_details->water_saved;
        if (!empty($order_details->total_amount) && !empty($order_details->sprayed_acreage)) {
            $crop_base_price = $order_details->total_amount / $order_details->sprayed_acreage;
            $acerage  = $order_details->sprayed_acreage;
            $water_saved= $acerage* $water_saved_qty;
        } else if (!empty($order_details->total_amount) && !empty($order_details->requested_acreage) && empty($order_details->sprayed_acreage)) {
            $crop_base_price = $order_details->total_amount / $order_details->requested_acreage;
            $acerage  = $order_details->requested_acreage;
            $water_saved = $acerage * $water_saved_qty;

        }



        $html .= '<tr>
                                        <td style="border-bottom: 1px solid #83838370">
                                            <table class="innerTable">
                                                <tr>
                                                    <td class="verticalTop">Nature of Service</td>
                                                    <td class="verticalTop">
                                                        : Support services to crop production
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="verticalTop">SAC</td>
                                                    <td class="verticalTop">: 998611</td>
                                                </tr>
                                                <tr>
                                                    <td class="verticalTop">Crop</td>
                                                    <td class="verticalTop">: ' . @$order_details->crop_name . '</td>
                                                </tr>
                                                <tr>
                                                    <td class="verticalTop">Water Saved</td>
                                                    <td class="verticalTop">: ' . $water_saved . ' Ltr'. '</td>
                                                </tr>
                                                <tr>
                                                    <td class="verticalTop">Date of Service</td>
                                                    <td class="verticalTop">: ' . date('d-m-Y', strtotime(@$order_details->spray_date)) . '</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td
                                            class="textRight verticalTop"
                                            style="
                                                padding-top: 8px;
                                                width: 120px;
                                                border-bottom: 1px solid #83838370;
                                            "
                                        >
                                        Rs. ' . $crop_base_price . '
                                        </td>
                                        <td
                                            class="textRight verticalTop"
                                            style="
                                                padding-top: 8px;
                                                width: 100px;
                                                border-bottom: 1px solid #83838370;
                                            "
                                        >
                                       ' . $acerage . '
                                        </td>
                                        <td
                                            class="textRight verticalTop"
                                            style="
                                                padding-top: 8px;
                                                width: 130px;
                                                border-bottom: 1px solid #83838370;
                                            "
                                        >
                                        Rs.  ' . $order_details->total_amount . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td
                                            colspan="2"
                                            class="textRight p-0"
                                            style="
                                                padding-top: 10px !important;
                                                font-size: 14px;
                                                line-height: 22px;
                                            "
                                        >
                                            Total Basic Amount
                                        </td>
                                        <td
                                            class="p-0 textRight"
                                            style="
                                                padding-top: 10px !important;
                                                font-size: 14px;
                                                line-height: 22px;
                                            "
                                        >
                                        Rs.  ' . $order_details->total_amount  . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td
                                            colspan="2"
                                            class="textRight p-0"
                                            style="font-size: 14px; line-height: 22px"
                                        >
                                            Discount</td>
                                        <td
                                            class="p-0 textRight"
                                            style="font-size: 14px; line-height: 22px"
                                        >
                                            - Rs.  ' . $order_details->total_discount  . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td
                                            colspan="2"
                                            class="textRight p-0"
                                            style="font-size: 14px; line-height: 22px"
                                        >
                                            GST (NIL)
                                        </td>
                                        <td
                                            class="p-0 textRight"
                                            style="font-size: 14px; line-height: 22px"
                                        >
                                        Rs. 0
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td
                                            colspan="2"
                                            class="textRight p-0 size4 verticalTop"
                                            style="
                                                border-top: 1px solid;
                                                padding-top: 10px !important;
                                                white-space: nowrap;
                                            "
                                        >
                                            <strong>Total Invoice Amount</strong>
                                        </td>
                                        <td
                                            class="p-0  textRight verticalTop"
                                            style="border-top: 1px solid; padding-top: 10px !important"
                                        >
                                        <strong>Rs. ' . $order_details->total_payable_amount  . '</strong><br/>
                                        </td>
                                    </tr>
                                    <tr>
                                    <td colspan="4" class="textRight p-0 size1 verticalTop"
                                    style="
                                        padding-top: 10px !important;
                                        white-space: nowrap;
                                    ">
                                     <strong>In Words: '
            . NumberToWords::convert($order_details->total_payable_amount) .
            ' Only</strong></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>

                            <td colspan="2">
                                <table style="width: 100%; margin-top: 0.7rem">
                                    <tr>
                                        <td style="width: 65%">&nbsp;</td>
                                        <td class="textCenter">
                                            <p class="pdfHeading" style="font-size: 13px; text-decoration: none; padding-top: 1rem; padding-left: 0">
                                            This invoice has been electronically signed and does not require a physical signature
                                        </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="footer">
                    <p class="pdfHeading" style="margin-top: 1rem; font-size: 12px">
                        Terms & Conditions
                    </p>

                    <ul>
                       <li>
                Service Scope: This invoice covers our AgriWings Drone Spray Service,
                limited to the application of pesticides, fertilizers, and other crop treatments
                provided by the farmer. We shall not be responsible for the supply, selection,
                or performance of any agri-inputs products used during the Service.
            </li>
            <li>
                Service Charges: The Farmer agrees to pay the Provider the agreed-upon
                Service charges for the AgriWings Drone Spray Services. Payment through
                Online method only. GST exempted Service as per notification No. 12/2017-
                Central Tax (Rate) dated 28.6. 2017 issued by Ministry of finance,
                Government of India. Services related to the cultivation of plants, rearing of
                all life forms of animals, and the care of their products are exempt from GST.
                Compliance with Laws: Our AgriWings Drone Spray operations will comply
                with all applicable agricultural laws, regulations, and guidelines set forth by
                the Indian Government and agricultural authorities.
            </li>
            <li>
                Liability Limitation: While we strive for the utmost precision and efficiency in
                our AgriWings Drone Spray Service, we shall not be held liable for any non-
                performance, ineffectiveness, or adverse effects of the agri-inputs provided
                by the farmer. The farmer assumes full risk and responsibility for the quality,
                suitability, and outcome of the agri-inputs used.
                Prior Assessment: Before commencing any AgriWings Drone Spray
                operations, we shall conduct a thorough assessment of the designated
                agricultural area. However, it is the farmer' . '' . 's responsibility to provide
                accurate and up-to-date information about the area, ensuring the efficacy of
                the Service.
            </li>
            <li>
                Operator Competence: Our AgriWings Drone Spray Service is conducted by
                operators who possess the necessary expertise to perform the spraying
                operations safely and efficiently.
                Farmer' . '' . 's Obligations: The farmer, in this case, the farmer, is solely
                responsible for purchasing and supplying the appropriate agri-inputs for the
                AgriWings Drone Spray Service. It is essential for the farmer to select high-
                quality and suitable products for the desired agricultural outcomes.
            </li>
            <li>Crop Damage: In the unlikely event of any crop damage or unintended
                effects due to the application of agri-inputs provided by the farmer, we shall
                not be held liable. The farmer must promptly address any issues arising from
                the agri-input' . '' . 's performance.
            </li>
            <li>
                Indemnification: By availing of our AgriWings Drone Spray Service, the
                farmer agrees to indemnify and hold our company, its employees, and
                agents harmless from any claims, liabilities, costs, and expenses arising out
                of or related to the use and performance of the agri-inputs provided by the
                farmer.
            </li>
            <li>Modification: These terms and conditions may be amended or updated as
                required. The latest version will be made available to the farmer and
                communicated through official channels.
                Dispute Resolution: In the event of any disputes or claims arising from the
                AgriWings Drone Spray Service, both parties shall make reasonable efforts
                to settle the matter amicably through negotiations. The jurisdiction of courts
                will be at our Regd. Office only.
            </li>
            <li>Force Majeure: The performance of the Service may be totally or partially
                suspended by the company during any period in which the company may be
                prevented or hindered from the performance of the Services because of
                circumstances beyond the reasonable control of the company including but
                not limited to fire, storm, flood, cyclone, earthquake, pandemic, act of terror,
                war, riots, and strike.
            </li>
                        <li style="margin-bottom: 12px">
                        Farmer’s Helpdesk no: 9889161313
                        </li>
                    </ul>

                    <p class="footerAdrress">
                    ' . @$order_details->companyName->registered_address . '
                    </p>
                </div>
            </body>
        </html>
        ';

        // $pdf = PDF::loadView($html);
        // // Helper::logAction('Download', 'download invoice pdf');
        // // $pdf->setPaper('A4', 'portrait');
        // return $pdf->stream('sampleTest.pdf');


        // $pdf = PDF::loadView($html);
        // return $pdf->stream('sampleTest.pdf');

        // Helper::logAction('Download', 'download invoice pdf');
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($html);
        // $customPaper = array(0,0,300,1440);
        $pdf->setPaper('A4', 'portrait');
        // $pdf->save(public_path() . '/consignment-pdf/invoice.pdf')->stream('invoice.pdf');

        return $pdf->stream('document.pdf');

        // return $pdf->download('Inovice.pdf');

    }

    private function generateStateCode($stateName)
    {
        // Find the state code based on the state name in the state array
        $stateCode = array_search($stateName, $this->stateArray);

        // If state code not found, you might want to handle this case accordingly
        if (!$stateCode) {
            // You can throw an exception or use a default value
            $stateCode = 'UnknownState';
        }

        return $stateCode;
    }

    public function redirect_to_invoice($id)
    {
        // Check if $id is a valid base64-encoded string
        if (!preg_match('/^[a-zA-Z0-9\/\+]*={0,2}$/', $id)) {
            return 'ID is not applicable for this';
        }

        // Decode the base64-encoded string
        $decodedId = base64_decode($id);

        // Validate if the decoded string is a valid number
        if (!is_numeric($decodedId)) {
            return 'ID is not applicable for this';
        }

        return self::generate_invoice_pdf($id);
    }

    public static function send_invoice_sms($id)
    {

        // return 1;
        // $id=3;

        $service_table=Services::with('assetOperator', 'farmerDetails')->where('id',$id)->first();
        if(empty($service_table))
        {
            return "Not valid order";
        }
        $farmer_name=$service_table->farmer_name;
        $order_id  = $service_table->order_id;
        $operator_name= $service_table->assetOperator->name;
        $payable_amount    = $service_table->total_payable_amount;

        $API = "PY95H00rx0aSJP7v8ofVsA"; // GET Key from SMS Provider
        $peid = "1701168155524038890"; // Get Key from DLT
        $sender_id = "AGRWNG"; // Approved from DLT

        $live_host_name = request()->getHttpHost();
// return $live_host_name;
        // || $live_host_name == "ter.etsbeta.com"
      
        if ($live_host_name == 'localhost:8000' || $live_host_name == 'new.agriwings.in') {
            //ramakant  $mob = '9878616117'; 
            $mob = '8146586644'; 
        }else{
            $mob = $service_table->farmerDetails->farmer_mobile_no;
            if (empty($mob)) {
                return '';
            }
        }
      
        // $mob = '8529698369'; // Get Mobile Number from Sender
      
        // print_r($getsender);
        // exit;

           $invoice_url= 'https://new.agriwings.in/zp/'.base64_encode($id);
        //    $invoice_url = 'https://new.agriwings.in/inv';
        //    return $invoice_url;
     
        $umsg= "Dear $farmer_name, Your AgriWings order no $order_id has been completed by $operator_name. Invoice: $invoice_url of  Rs $payable_amount Thanks for choosing AgriWings.";
        // $umsg = "Dear $name , your TER for Period 12-23-2933 to 12-23-2976 has been received and is under process. TER UNID is $UNID Thanks! Frontiers";
      

        $url = 'http://sms.innuvissolutions.com/api/mt/SendSMS?APIkey=' . $API . '&senderid=' . $sender_id . '&channel=Trans&DCS=0&flashsms=0&number=' . urlencode($mob) . '&text=' . urlencode($umsg) . '&route=2&peid=' . urlencode($peid) . '';

    //    $this->SendTSMS($url);
        self::SendTSMS($url);

        // return 1;
        
        // $API = "cBQcckyrO0Sib5k7y9eUDw"; // GET Key from SMS Provider
        // $peid = "1201159713185947382"; // Get Key from DLT
        // $sender_id = "FAPLHR"; // Approved from DLT
        // $mob = '9876543424234243'; // Get Mobile Number from Sender
        // $name = 'AgriWings';
        // // print_r($getsender);
        // // exit;


        // $UNID = '123';
        // $umsg = "Dear $name , your TER for Period 12-23-2933 to 12-23-2976 has been received and is under process. TER UNID is $UNID Thanks! Frontiers";

        // $url = 'http://sms.innuvissolutions.com/api/mt/SendSMS?APIkey=' . $API . '&senderid=' . $sender_id . '&channel=Trans&DCS=0&flashsms=0&number=' . urlencode($mob) . '&text=' . urlencode($umsg) . '&route=2&peid=' . urlencode($peid) . '';

        // $this->SendTSMS($url);
    }

    public static function SendTSMS($hostUrl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $hostUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // change to 1 to verify cert
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        $result = curl_exec($ch);
        return $result;
    }
}
