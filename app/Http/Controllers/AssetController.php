<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use App\Models\Battery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    //
    public function show_asset_list()
    {

        $asset_details = AssetDetails::where('status', 1)->get();

        if (!$asset_details->isEmpty()) {
            return [
                'data' => $asset_details,
                'statuscode' => '200',
                'msg' => 'Asset list fetched successfully.'
            ];
        } else {
            return [
                'status' => 'error',
                'statuscode' => '200',
                'data'=>[],
                'msg' => 'Assets not found.'
            ];
        }
    }

    public function available_asset_list()
    {

        $asset_details = AssetDetails::where('status', 1)->where('assigned_status',1)->get();

        if (!$asset_details->isEmpty()) {
            return [
                'data' => $asset_details,
                'statuscode' => '200',
                'msg' => 'Asset list fetched successfully.'
            ];
        } else {
            return [
                'status' => 'error',
                'statuscode' => '200',
                'data' => [],
                'msg' => 'Assets not found.'
            ];
        }
    }

    public function add_asset()
    {

        return view('assets.create_new_asset');
    }

    public function submit_asset_details(Request $request)
    {
        $data = $request->all();
        // return $data;
        $validator = Validator::make($request->all(), [
            'asset_details.capacity' => 'numeric',
            'asset_details.mfg_year' => 'required|numeric',
            'asset_details.model' => 'required|string|max:255',
            'asset_details.uin' => 'required|string|max:255',
            'asset_details.battery_ids' => 'nullable|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }


        // check asset   already exists
        $check_asset_already_exists = AssetDetails::where('uin', $data['asset_details']['uin'])->get();
        if ($check_asset_already_exists->count() > 0) {
            $result_array = [
                'status' => 'error',
                'statuscode' => '409',
                'msg' => 'Asset Already Exists with the same uin number',
                'data' => $check_asset_already_exists->toArray(), // if you need it as an array
            ];
            return response()->json($result_array, 200);
        }

        $details = Auth::user();


        if (!empty($data['asset_details']['battery_ids'])) {
            $battery_ids = explode(',', $data['asset_details']['battery_ids']);
            $errors = [];

            // Iterate over each battery ID
            foreach ($battery_ids as $battery_id) {
                $battery = Battery::find($battery_id);
                // return $battery->assigned_status;
                // Check if battery exists
                if (!$battery) {
                    $errors[] = "Battery with ID $battery_id does not exist.";
                } else {

                    // Check if battery is already assigned
                    if ($battery->assigned_status == 1) {
                        // return $errors;
                        $errors[] = "Battery with ID $battery_id is already assigned.";
                    }

                    if ($battery->battery_pair != 1) {
                        // return $errors;
                        $errors[] = "Battery with ID $battery_id is not paired.";
                    }
                }
            }

            // return "f";


            if (!empty($errors)) {
                // Send an API response with the errors
                $response = [
                    "status" => "error",
                    "message" => implode(' ', $errors)
                ];
                return response()->json($response, 400);
            }
        }


        $battery_exists = 0;

        if (!empty($data['asset_details']['battery_ids'])) {
            $data['asset_details']['assigned_date'] = date('Y-m-d');
            $data['asset_details']['assigned_status'] = 1;
            $battery_exists = 1;
        }

        $data['asset_details']['saved_by_name'] = $details->name;
        $data['asset_details']['saved_by_id'] = $details->id;
        $data['asset_details']['updated_by_name'] = "";
        $data['asset_details']['updated_by_id'] = "";
        $data['asset_details']['asset_name'] = 'Drone';
        // return $data;
        // Create or update the farmer details
        $asset_id = AssetDetails::select('asset_id')->latest('id')->first();
        // return $asset_id;
        $asset_id = json_decode(json_encode($asset_id), true);
        if (empty($asset_id) || $asset_id == null) {
            $initial_number = "HAWK-1";
            $data['asset_details']['asset_id'] = $initial_number;
        } else {
            $parts = explode('-', $asset_id['asset_id']);

            // Extract the numeric part and increment it
            $next_number = (int)$parts[1] + 1;

            // Concatenate the string and the incremented number
            $next_asset_id = $parts[0] . '-' . $next_number;
            $data['asset_details']['asset_id'] = $next_asset_id;
        }

        $AssetDetails = AssetDetails::create($data['asset_details']);

        if ($AssetDetails) {
            if ($battery_exists) {
                $battery_ids = explode(',', $data['asset_details']['battery_ids']);

                // Iterate over each battery ID
                foreach ($battery_ids as $battery_id) {
                    $update_battery = Battery::where('id', $battery_id)->update(['assigned_date' => date('Y-m-d'), 'assigned_status' => 1]);
                }
            }
        }




        if ($AssetDetails) {
            $response['status'] = 'success';
            $response['statuscode'] = '200';
            $response['msg'] = 'Assest Added Successfully...';
            return response()->json($response);
        } else {
            $response['status'] = 'error';
            $response['statuscode'] = '403';
            $response['msg'] = 'There is server problem. Record Not Saved.';
            return response()->json($response);
        }
    }

    public function edit_asset(Request $request)
    {
        $data = $request->all();
        // return $data;

        $asset_id = $data['asset_details']['id'];
        // return $asset_id;

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'asset_details.capacity' => 'numeric',
            'asset_details.mfg_year' => 'nullable|numeric',
            'asset_details.model' => 'nullable|string|max:255',
            'asset_details.battery_ids' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $details = Auth::user();


        // Update the asset details
        $data['asset_details']['updated_by_name'] = $details->name;
        $data['asset_details']['updated_by_id'] = $details->id;

        $asset = AssetDetails::where('id', $asset_id)->first();

        if (!$asset) {
            $response['success'] = false;
            $response['statuscode'] = '404';
            $response['msg'] = 'Asset not found.';
            return response()->json($response);
        }
        $battery_exists = 0;
        $battery_remove = 0;
        $removed_batteries = "";
        // start battery assign
        // if (!empty($data['asset_details']['battery_ids']) && $data['asset_details']['battery_ids'] != $asset->battery_ids) {
        //     $battery_ids = explode(',', $data['asset_details']['battery_ids']);
        //     $errors = [];

        //     // Iterate over each battery ID
        //     foreach ($battery_ids as $battery_id) {

        //         $battery = Battery::find($battery_id);
        //         // return $battery->assigned_status;
        //         // Check if battery exists
        //         if (!$battery) {
        //             $errors[] = "Battery with ID $battery_id does not exist.";
        //         } else {

        //             // Check if battery is already assigned
        //             if ($battery->assigned_status == 1) {
        //                 // return $errors;
        //                 $errors[] = "Battery with ID $battery_id is already assigned.";
        //             }
        //         }
        //     }

        //     // return "f";


        //     if (!empty($errors)) {
        //         // Send an API response with the errors
        //         $response = [
        //             "status" => "error",
        //             "message" => implode(' ', $errors)
        //         ];
        //         return response()->json($response, 400);
        //     }
        // }

        //  return [$data['asset_details']['battery_ids'],  $asset->battery_ids];

        // if (!empty($data['asset_details']['battery_ids']) && $data['asset_details']['battery_ids'] != $asset->battery_ids) {
        //     $data['asset_details']['assigned_date'] = date('Y-m-d');
        //     $data['asset_details']['status'] = 1;
        //     $battery_exists = 1;
        // }
        // end

        // start
        if (!empty($data['asset_details']['battery_ids']) && $data['asset_details']['battery_ids'] != $asset->battery_ids) {
            $new_battery_ids = explode(',', $data['asset_details']['battery_ids']);
            $existing_battery_ids = explode(',', $asset->battery_ids);

            // Find new battery IDs that are not already assigned
            $new_unassigned_battery_ids = array_diff($new_battery_ids, $existing_battery_ids);
            // Find existing battery IDs that are no longer assigned
            $removed_battery_ids = array_diff($existing_battery_ids, $new_battery_ids);

            // return [$new_unassigned_battery_ids, $removed_battery_ids];

            // Update assigned date and status if there are new unassigned battery IDs
            if (!empty($new_unassigned_battery_ids)) {
                // $data['battery_ids']= $data['asset_details']['battery_ids'];
                $data['asset_details']['assigned_date'] = date('Y-m-d');
                $data['asset_details']['assigned_status'] = 1;
                $battery_exists = 1;
            }

            
            // Remove existing battery IDs if they are no longer assigned
            if (!empty($removed_battery_ids)) {
                // Update the asset's battery_ids
                $removed_batteries = implode(',', $removed_battery_ids);
                $battery_remove = 1;
            }
        }

        // end

            if (empty($data['asset_details']['battery_ids']) && !empty($asset->battery_ids)) {
            $removed_batteries= $asset->battery_ids;
            $battery_remove = 1;
            }

        if(empty($data['asset_details']['battery_ids']))
        {
            $data['asset_details']['battery_ids']="";
            $data['asset_details']['assigned_status'] = 0;
            $data['asset_details']['assigned_date'] = null;


        }

        $AssetDetails =   $asset->update($data['asset_details']);

        if ($AssetDetails) {
            if ($battery_exists) {
                $battery_ids = explode(',', $data['asset_details']['battery_ids']);

                // Iterate over each battery ID
                foreach ($battery_ids as $battery_id) {
                    $update_battery = Battery::where('id', $battery_id)->update(['assigned_date' => date('Y-m-d'), 'assigned_status' => 1]);
                }
            }

            if ($battery_remove) {
                $battery_ids = explode(',', $removed_batteries);
                // return $removed_batteries;
                // Iterate over each battery ID
                foreach ($battery_ids as $battery_id) {
                    $update_battery = Battery::where('id', $battery_id)->update(['assigned_date' => null, 'assigned_status' => 0]);
                }
            }
        }

        $response['success'] = true;
        $response['statuscode'] = '200';
        $response['msg'] = 'Asset Updated Successfully...';
        return response()->json($response);
    }

    public function delete_asset(Request $request)
    {
        $data = $request->all();
        $asset_id = $data['id'];
      $check_asset=  AssetDetails::where('id', $asset_id)->first();

      if($check_asset->assigned_status)
      {

            $response['status'] = "error";
            $response['statuscode'] = '200';
            $response['data'] = $check_asset;
            $response['msg'] = 'Asset Already Assigned. Please remove the Asset Operator';
            return response()->json($response);
      }

        AssetDetails::where('id', $asset_id)->update(['status' => 0]);

        $response['status'] = "success";
        $response['statuscode'] = '200';
        $response['msg'] = 'Asset Deleted Successfully...';
        return response()->json($response);
    }


    public function test_upload(Request $request)
    {

        $file = $request->file('file');
        // dd(config('services.s3'));
        // dd($file);
        $path = $file->store('test', 's3');

        // Optionally, you can generate a publicly accessible URL
        $url = Storage::disk('s3')->url($path);

        return response()->json(['url' => $url]);
    }
}
