<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\AssetOperator;
use App\Models\OrdersTimeline;
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
            $asset_id= $check_asset_id->asset_id;
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
        $asset_operators = AssetOperator::select('id', 'code', 'name')->where('asset_id','!=','' )->where('status', 1)->get();

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
            $fetch_assigned_orders = Services::with('crop', 'farmerDetails', 'farmLocation')->where('asset_operator_id', $fetch_operator_details->id)->where('order_status', 3)->get();
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
        $data=$request->all();
        $validatedData = $request->validate([
            'id' => 'required|numeric',
            'chemical_used_ids' => 'required|string', // Assuming chemical_used_ids is a comma-separated string
            'farmer_available' => 'required|boolean',
            'fresh_water' => 'required|boolean',
            'noc_image' => 'image|mimes:jpeg,png,jpg,gif',
        ]);
        // return $data;
        $id=$data['id'];
        $check_order_exists=Services::where('id',$id)->first();
// return $check_order_exists;
        if(empty($check_order_exists))
        {
            return response()->json(['msg' => 'Service Does not exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);

        }else{
            $noc_image = $request->file('noc_image');
            if (!empty($noc_image)) {
                // Generate a random string for the filename
                $randomString = Str::random(10); // Adjust the length as needed

                // Concatenate the random string with the desired file extension
                $customFilename = 'Noc_' . $randomString . '.' . $noc_image->getClientOriginalExtension();

                // Specify the filename when storing the file in S3
                $path = $noc_image->storeAs('aadhar', $customFilename, 's3');

                // Optionally, you can generate a publicly accessible URL
                $url = Storage::disk('s3')->url($path);
                // print_r($url);
                $data['noc_image'] = $customFilename;
            }
            $details = Auth::user();

            $data['spray_started_created_by_id'] = $details->id;
            $data['spray_started_created_by'] = $details->name;
            $data['spray_started_date'] =   date('Y-m-d');

            $update_services_done=Services::where('id',$id)->update(['spray_date' => date('Y-m-d'), 'spray_status'=>1, 'order_status'=>4]);
            if($update_services_done)
            {
                $update_services = OrdersTimeline::where('id', $check_order_exists->order_details_id)->update($data);

            }

            if($update_services)
            {
                return response()->json(['msg' => 'Spray Started Successfully..', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);

            }

        }

    }
}
