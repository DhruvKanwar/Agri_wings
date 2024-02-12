<?php

namespace App\Http\Controllers;

use App\Models\AssetOperator;
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



        $check_asset_id = AssetOperator::where('asset_id',$data['asset_id'])->first();

        if(!empty($check_asset_id))
        {
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

        if(!empty($data['asset_id']))
        {
            $data['assigned_date'] = date('Y-m-d');
            $data['assigned_status'] = 1;
        }


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


        $data['user_id'] = $user->id;
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
        if(!empty($data['asset_id']))
        {
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
        }else{
         
            $assign_flag = 1;
        }
    }else{
            $remove_flag = 1;
    }

    $check_asset_operator=AssetOperator::where('id',$data['id'])->first();

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
        if ($assign_flag) {
            $data['assigned_date'] = date('Y-m-d');
            $data['assigned_status'] = 1;
        }
        if($remove_flag)
        {
            $data['assigned_date']=null;
            $data['assigned_status']=0;
        }
        // return [$remove_flag,$assign_flag];
        $assetOperator = AssetOperator::where('id', $data['id'])->update($data);

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

            if ($assetOperator) {
                AssetOperator::where('id', $id)->update(['end_date' => $end_date, 'status' => 0]);
                $assetOperator->delete();
                return response()->json(['statuscode' => '200', 'status' => 'success',  'msg' => 'Record deleted successfully']);
            } else {
                return response()->json(['statuscode' => '200', 'status' => 'success',  'msg' => 'Record not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['statuscode' => '400', 'status' => 'error',  'msg' => 'Error deleting record', 'data' => $e->getMessage()], 500);
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
}
