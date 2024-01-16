<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    //

    public function submit_vehicle_details(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'operator_id' => 'nullable|string',
            'owned_by' => 'required|string',
            'type' => 'required|string',
            'registration_no' => 'required|string',
            'chassis_no' => 'nullable|string',
            'engine_no' => 'required|string',
            'manufacturer' => 'required|string',
            'year_of_make' => 'nullable|integer',
            'assigned_to_operator' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Check if the registration number already exists
        $existingVehicle = Vehicle::where('registration_no', $request->input('registration_no'))->first();
        $data = $request->all();

        if ($existingVehicle) {

            $result_array = array(
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'Registration number already exists '. $data['registration_no'],
            );
            return response()->json($result_array, 200);
        }

        $details = Auth::user();

        $data['saved_by_name'] = $details->name;
        $data['saved_by_id'] = $details->id;
        $data['updated_by_name'] = "";
        $data['updated_by_id'] = "";

        $vehicle = new Vehicle($data);

        // Save the model to the database
        $res = $vehicle->save();
        if($res)
        {
            $result_array = array(
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'Vehicle saved successfully',
            );
            return response()->json($result_array, 200);
        }else{
            $result_array = array(
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Record not been stored.'
            );
            return response()->json($result_array, 200);
        }
   
    }

    public function edit_vehicle_details(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'operator_id' => 'nullable|string',
            'operator_name' => 'nullable|string',
            'owned_by' => 'required|string',
            'type' => 'required|string',
            'registration_no' => 'required|string',
            'chassis_no' => 'nullable|string',
            'engine_no' => 'required|string',
            'manufacturer' => 'required|string',
            'year_of_make' => 'nullable|integer',
            'assigned_to_operator' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Check if the registration number already exists
        $existingVehicle = Vehicle::where('registration_no', $request->input('registration_no'))->first();

        if (!$existingVehicle) {
            $result_array = array(
                'status' => 'error',
                'statuscode' => '404',
                'msg' => 'Vehicle not found with registration number ' . $request->input('registration_no'),
            );
            return response()->json($result_array, 404);
        }

        $details = Auth::user();

        $existingVehicle->update([
            'operator_id' => $request->input('operator_id'),
            'operator_name' => $request->input('operator_name'),
            'owned_by' => $request->input('owned_by'),
            'type' => $request->input('type'),
            'chassis_no' => $request->input('chassis_no'),
            'engine_no' => $request->input('engine_no'),
            'manufacturer' => $request->input('manufacturer'),
            'year_of_make' => $request->input('year_of_make'),
            'assigned_to_operator' => $request->input('assigned_to_operator'),
            'updated_by_name' => $details->name,
            'updated_by_id' => $details->id,
        ]);

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Vehicle updated successfully',
        );
        return response()->json($result_array, 200);
    }


 

}
