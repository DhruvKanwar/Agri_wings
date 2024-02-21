<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Battery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BatteryController extends Controller
{
    public function submit_battery_details(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'battery_code' => 'required|string',
            'battery_type' => 'required|string',
            // 'status' => 'required|string',
            'battery_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Check if the battery code already exists
        $existingBatteryCode = Battery::where('battery_code', $request->input('battery_code'))->first();

        if ($existingBatteryCode) {
            $result_array = [
                'status' => 'error',
                'statuscode' => '422',
                'msg' => "Battery code {$request->input('battery_code')} already exists.",
            ];
            return response()->json($result_array, 200);
        }

        // Get the input ID from the request
        $inputId = $request->input('battery_id');

        // Check the availability of slots for the given ID
        $existingSlots = Battery::where('battery_id', 'like', "{$inputId}-%")->where('status',1)->pluck('battery_id')->toArray();

        // return [$inputId,$existingSlots];
        // Check if both A and B slots are occupied for the input ID
        $isSlotAOccupied = in_array("{$inputId}-A", $existingSlots);
        $isSlotBOccupied = in_array("{$inputId}-B", $existingSlots);

        if ($isSlotAOccupied && $isSlotBOccupied) {
            $result_array = [
                'status' => 'error',
                'statuscode' => '422',
                'msg' => "Both A and B slots are already booked for Battery ID {$inputId}.",
            ];
            return response()->json($result_array, 200);
        }

        // Determine the next available slot letter
        $nextSlotLetter = $isSlotAOccupied ? 'B' : 'A';

        // Construct the next available slot
        $nextAvailableSlot = "{$inputId}-{$nextSlotLetter}";

        // Check if the battery_id already exists
        $existingBattery = Battery::where('battery_id', $nextAvailableSlot)->first();

        if ($existingBattery) {
            $result_array = [
                'status' => 'error',
                'statuscode' => '422',
                'msg' => "Battery ID {$nextAvailableSlot} already exists.",
            ];
            return response()->json($result_array, 200);
        }

        // Get the authenticated user details
        $details = Auth::user();


        // Create an array with the request data and additional details
        $data = [
            'battery_id' => $nextAvailableSlot,
            'battery_code' => $request->input('battery_code'),
            'battery_type' => $request->input('battery_type'),
            'saved_by_name' => $details->name,
            'saved_by_id' => $details->id,
            'updated_by_name' => null,
            'updated_by_id' => null,
        ];

        // Create a new Battery record
        $battery = new Battery($data);

        // Save the model to the database
        $res = $battery->save();

        if ($res) {
            $explode_slot = explode('-', $nextAvailableSlot);

            if ($explode_slot[1] == 'B') {
                $battery_ids = [$explode_slot[0] . '-A', $explode_slot[0] . '-B'];
                $update_battery_details = Battery::whereIn('battery_id', $battery_ids)->update(['battery_pair' => '1']);
            }
        }




        if ($res) {
            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'Battery saved successfully',
            ];
            return response()->json($result_array, 200);
        } else {
            $result_array = [
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Record not been stored.',
            ];
            return response()->json($result_array, 200);
        }
    }



    public function edit_battery_details(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            // 'battery_code' => 'required|string',
            // 'battery_type' => 'required|string',
            'status' => 'required|string',
            'battery_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // // Check if the battery code already exists for a different battery_id
        // $existingBatteryCode = Battery::where('battery_code', $request->input('battery_code'))
        //     ->where('battery_id', '!=', $request->input('battery_id'))
        //     ->first();

        // if ($existingBatteryCode) {
        //     $result_array = [
        //         'status' => 'error',
        //         'statuscode' => '422',
        //         'msg' => "Battery code {$request->input('battery_code')} already exists for a different Battery ID.",
        //     ];
        //     return response()->json($result_array, 200);
        // }

        // Get the authenticated user details
        $details = Auth::user();



        // $data['battery_code']= $request->input('battery_code');
        // $data['battery_type'] = $request->input('battery_type');
        // if(!empty($request->input('status')))
        // {
        //     $data['status'] = $request->input('status');
        // }

        $data['status'] = $request->input('status');
        $data['battery_code'] = $request->input('battery_code');
        $data['battery_type'] = $request->input('battery_type');

        $data['battery_pair'] = 0;
        $data['updated_by_name'] = $details->name;
        $data['updated_by_id'] = $details->id;
        $check_assigned_battery = Battery::where('battery_id', $request->input('battery_id'))->first();
        
        if (!(empty($check_assigned_battery))) {

            if ($check_assigned_battery->assigned_status) {
                $result_array = [
                    'status' => 'error',
                    'statuscode' => '200',
                    'msg' => 'Record not been updated. Battery Already Assigned.',
                ];
                return response()->json($result_array, 200);
            }
        } else {
            $result_array = [
                'status' => 'error',
                'statuscode' => '200',
                'msg' => ' Battery Does not exists.',
            ];
            return response()->json($result_array, 200);
        }


        // Update the existing Battery record
        $battery = Battery::where('battery_id', $request->input('battery_id'))->update($data);

        if ($battery) {
            $explode_slot = explode('-', $request->input('battery_id'));

            if ($explode_slot[1] == 'B') {
                $check_battery_exists = Battery::where('battery_id', $explode_slot[0] . '-A')->first();
                if (!empty($check_battery_exists)) {
                    $update_battery_details = Battery::where('battery_id', $explode_slot[0] . '-A')->update(['battery_pair' => '0']);
                }
            } else {
                $check_battery_exists = Battery::where('battery_id', $explode_slot[0] . '-B')->first();
                if (!empty($check_battery_exists)) {
                    $update_battery_details = Battery::where('battery_id', $explode_slot[0] . '-B')->update(['battery_pair' => '0']);
                }
            }
        }


        if ($battery) {
            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'Battery details updated successfully',
            ];
            return response()->json($result_array, 200);
        } else {
            $result_array = [
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Record not been updated.',
            ];
            return response()->json($result_array, 200);
        }
    }

    public function get_all_batteries()
    {
        // Retrieve all batteries from the database
        $batteries = Battery::get();

        // Check if any batteries are found
        if ($batteries->isEmpty()) {
            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'No batteries found.',
                'data' => [],
            ];
        } else {
            // Prepare the list of batteries
            $batteryList = $batteries->map(function ($battery) {
                return [
                    'id' => $battery->id,
                    'battery_code' => $battery->battery_code,
                    'battery_type' => $battery->battery_type,
                    'status' => $battery->status,
                    'battery_id' => $battery->battery_id,
                ];
            });

            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'List of all batteries.',
                'data' => $batteryList,
            ];
        }

        // Return the response
        return response()->json($result_array, 200);
    }


    public function get_battery_by_id(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:batteries,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Retrieve the battery from the database based on the provided ID
        $battery = Battery::find($request->input('id'));

        // Check if the battery is found
        if (!$battery) {
            $result_array = [
                'status' => 'error',
                'statuscode' => '404',
                'msg' => 'Battery not found.',
            ];
        } else {
            // Prepare the battery details
            $batteryDetails = [
                'id' => $battery->id,
                'battery_code' => $battery->battery_code,
                'battery_type' => $battery->battery_type,
                'status' => $battery->status,
                'battery_id' => $battery->battery_id,
                'saved_by_name' => $battery->saved_by_name,
                'saved_by_id' => $battery->saved_by_id,
                'updated_by_name' => $battery->updated_by_name,
                'updated_by_id' => $battery->updated_by_id,
                'created_at' => $battery->created_at,
                'updated_at' => $battery->updated_at,
            ];

            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'Battery details.',
                'battery' => $batteryDetails,
            ];
        }

        // Return the response
        return response()->json($result_array, 200);
    }

    public function get_batteries_to_assign()
    {
        // Retrieve all batteries from the database
        $batteries = Battery::where('status', 1)->where('battery_pair', 1)->get();

        // Check if any batteries are found
        if ($batteries->isEmpty()) {
            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'No batteries found.',
                'data' => [],
            ];
        } else {
            // Prepare the list of batteries
            $batteryList = $batteries->map(function ($battery) {
                return [
                    'id' => $battery->id,
                    'battery_code' => $battery->battery_code,
                    'battery_type' => $battery->battery_type,
                    'assign_status' => $battery->assigned_status,
                    'status' => $battery->status,
                    'battery_id' => $battery->battery_id,
                ];
            });

            $result_array = [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'List of all batteries.',
                'data' => $batteryList,
            ];
        }

        // Return the response
        return response()->json($result_array, 200);
    }
}
