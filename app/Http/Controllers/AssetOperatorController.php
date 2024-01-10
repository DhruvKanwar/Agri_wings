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
            'dl_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'aadhaar_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as needed
            'start_date' => 'required|date',
            'user_id' => 'nullable|string|max:255',
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
        $data = $validator->validated();

        // return $data;

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
                'userdata' => $check_registered_user
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
        $data['vehicle_id'] = "stored id in vehicle table";



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
        return response()->json(['message' => 'Details stored successfully', 'data' => $assetOperator]);
    }
}
