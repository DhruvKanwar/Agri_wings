<?php

namespace App\Http\Controllers;

use App\Models\CropPrice;
use App\Models\RegionalClient;
use Illuminate\Http\Request;
use App\Models\Scheme;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use DB;


class SchemeController extends Controller
{
    public function get_scheme_list()
    {
        // Retrieve all schemes
        $schemes = Scheme::all();

        return response()->json(['status' => 'success', 'statuscode' => '200', 'data' => $schemes, 'msg' => 'Scheme List Fetched Successfully...']);
    }

    public function show($id)
    {
        // Retrieve a specific scheme by ID
        $scheme = Scheme::find($id);

        if (!$scheme) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        return response()->json(['data' => $scheme]);
    }

    public function submit_scheme_details(Request $request)
    {
        // return "Ds";
        // Validate request data
        $rules = [
            'type' => 'required|string',
            // 'scheme_code' => 'required|string|unique:schemes',
            'crop_id' => 'required|string',
            'crop_name' => 'required|string',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'crop_base_price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'min_acreage' => 'nullable|integer',
            'max_acreage' => 'nullable|integer|gte:min_acreage',
            'client_id' => 'nullable|string',
        ];



        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()]);
        }

        $data = $request->all();

        if (!empty($data['client_id'])) {
            $check_scheme_exists = Scheme::where('type', $data['type'])->where('crop_id', $data['crop_id'])->where('client_id', $data['client_id'])->where('status', 1)->get();
        } else {
            $check_scheme_exists = Scheme::where('type', $data['type'])->where('crop_id', $data['crop_id'])->where('status', 1)->get();
            $data['client_id'] = '';
        }
        // return [$check_scheme_exists];
        foreach ($check_scheme_exists as $scheme) {
            $periodToDatabase = strtotime(date('Y-m-d', strtotime($scheme->period_to)));
            $periodFromInput = strtotime(date('Y-m-d', strtotime($data['period_from'])));
    //   return [$scheme->period_to, $data['period_from'], $periodToDatabase, $periodFromInput];

            // var_dump($periodFromInput, $periodToDatabase);
            // Check if $data['period_from'] is greater than the 'period_to' from the database
            if ($periodFromInput <= $periodToDatabase) {
                return response()->json(['status' => 'error', 'data' => "Scheme Id : " . $scheme->id, 'statuscode' => '400', 'msg' => 'Invalid Period From Date. It should be greater than the existing scheme Period To Date.']);
            }
        }
        // return $check_scheme_exists;

        // Create a new scheme
        $details = Auth::user();
        $data['saved_by_name'] = $details->name;
        $data['saved_by_id'] = $details->id;

        // type 1=> general scheme,2=>Client Scheme , 3=> Subvention Scheme, 4=> R & D,5=> Demo

        // return $data['client_id'];
        if (!empty($data['client_id'])) {
            $get_client_details = DB::table('regional_clients')->where('id', $data['client_id'])->first();
            // $get_client_details = RegionalClient::where('id', $data['client_id'])->get();
            // return $get_client_details;
            $client_name = $get_client_details->regional_client_name;
        }

        if ($data['type'] == 1) {
            if (!empty($data['client_id'])) {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'General'. '-' .$client_name;
            } else {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'General';
            }
        }

        if ($data['type'] == 2) {
            if (!empty($data['client_id'])) {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'Client'. '-' .$client_name;
            } else {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'Client';
            }
        }

        if ($data['type'] == 3) {
            if (!empty($data['client_id'])) {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'Subvention'. '-' .$client_name;
            } else {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'Subvention';
            }
        }

        if ($data['type'] == 4) {
            if (!empty($data['client_id'])) {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'R & D'. '-' .$client_name;
            } else {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'R & D';
            }
        }

        if ($data['type'] == 5) {
            if (!empty($data['client_id'])) {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'Demo'. '-' .$client_name;
            } else {
                $data['scheme_name'] = $data['crop_name'] . '-' . 'Demo';
            }
        }



        // Query the database to get the latest farmer code for the state
        $latestCode = Scheme::select('scheme_code')
        ->orderBy('id', 'desc')
        ->first();

        // Generate the new farmer code
        if (empty($latestCode)) {
            $data['scheme_code'] ='Scheme-00001'; 
        } else {
            $parts = explode('-', $latestCode->scheme_code);
            $lastNumber = end($parts);
            $nextNumber = (int)$lastNumber + 1;
            $formattedNextNumber = sprintf('%05d', $nextNumber);
            $data['scheme_code'] = 'Scheme-' . $formattedNextNumber;

        }




        $scheme = Scheme::create($data);

        return response()->json(['msg' => 'Scheme created successfully', 'status' => 'success', 'statuscode' => '201', 'data' => $scheme], 201);
    }

    public function update_scheme(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'id' => 'required|string',
            'status' => 'boolean',
            'remarks' => 'string|nullable'
        ]);
        $data = $request->all();
        $id = $data['id'];
        $scheme = Scheme::find($id);

        if (!$scheme) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        $updated_data['status'] = 0;
        $updated_data['remarks'] =$data['remarks'];
        $scheme->delete();

        // Update the scheme
        $scheme->update($updated_data);

        return response()->json(['msg' => 'Scheme updated successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $scheme]);
    }

    public function destroy($id)
    {
        // Find the scheme by ID
        $scheme = Scheme::find($id);

        if (!$scheme) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        // Delete the scheme
        $scheme->delete();

        return response()->json(['msg' => 'Scheme deleted successfully', 'status' => 'success', 'statuscode' => '200']);
    }
}
