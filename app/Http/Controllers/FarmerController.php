<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FarmerDetails;
use App\Models\FarmDetails;
use App\Exports\ExportFarmerDetail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LocationData;


class FarmerController extends Controller
{
    //

    public function show_farmer_list()
    {
        $farmDetailData = FarmerDetails::with('FarmInfo')->get();
        return view('farmers.farmer-list', ['farmer_details' => $farmDetailData]);


        if (!empty($farmDetailData)) {
            return ['farmer_details' => $farmDetailData, 'statuscode' => '200', 'msg' => 'Farmers list fetched sucessfully..'];
        } else {
            return ['statuscode' => '200', 'msg' => 'Farmers not Found'];
        }
        // api
        // web

    }

    public function add_farmers()
    {
        $location_datas = LocationData::select('state_name', 'subdistrict_name')
            ->distinct()
            ->get();
        // return ['location_datas' => $location_datas];
        return view('farmers.create_new_farmer', ['location_datas' => $location_datas]);
    }

    public function location_datas()
    {
        $location_datas = LocationData::select('state_name', 'subdistrict_name')
            ->distinct()
            ->get();
        if (!empty($location_datas)) {
            return ['location_datas' => $location_datas, 'statuscode' => '200', 'msg' => 'Location Fetched Suceessfully.'];
        } else {
            return [ 'statuscode' => '200', 'msg' => 'Location Not Found...'];
        }
    }

    public function submit_farmer_details(Request $request)
    {
        $data = $request->all();
        // return $data;
        $request->validate([
            'farmer_details' => 'required|array',
            'farmer_details.farm_addresses' => 'required|array|min:1', // Ensure at least one farm address is provided

            'farmer_details.farmer_name' => 'required|string|max:255',
            'farmer_details.farmer_mobile_no' => 'required|string|max:15', // Adjust max length based on actual requirements
            'farmer_details.farmer_pincode' => 'required|string|max:10', // Assuming pin code is a string, adjust as needed
            'farmer_details.farmer_district' => 'required|string|max:255',
            'farmer_details.farmer_state' => 'required|string|max:255',
            'farmer_details.farmer_village' => 'required|string|max:255',
            'farmer_details.farmer_sub_district' => 'required|string|max:255',
            'farmer_details.farmer_address' => 'required|string',

            'farmer_details.farm_addresses.*.field_area' => 'nullable|string|max:255',
            'farmer_details.farm_addresses.*.pin_code' => 'required|string|max:10', // Assuming pin code is a string, adjust as needed
            'farmer_details.farm_addresses.*.village' => 'required|string|max:255',
            'farmer_details.farm_addresses.*.sub_district' => 'required|string|max:255',
            'farmer_details.farm_addresses.*.acerage' => 'required|string|max:255',
            'farmer_details.farm_addresses.*.district' => 'required|string|max:255',
            'farmer_details.farm_addresses.*.state' => 'required|string|max:255',
            'farmer_details.farm_addresses.*.address' => 'required|string',
        ]);
        $farm_details = $data['farmer_details']['farm_addresses'];

        unset($data['farmer_details']['farm_addresses']);
        $details = Auth::user();

        $data['farmer_details']['saved_by_name'] = $details->name;
        $data['farmer_details']['saved_by_id'] = $details->id;
        $data['farmer_details']['updated_by_name'] = "";
        $data['farmer_details']['updated_by_id'] = "";
        // return $data;
        // Create or update the farmer details
        $farmer_code = FarmerDetails::select('farmer_code')->latest('farmer_code')->first();
        $farmer_code = json_decode(json_encode($farmer_code), true);
        if (empty($farmer_code) || $farmer_code == null) {
            $initial_number = "1000001";
            $data['farmer_details']['farmer_code'] = $initial_number;
        } else {
            $data['farmer_details']['farmer_code'] = $farmer_code['farmer_code'] + 1;
        }

        $farmerDetails = FarmerDetails::create($data['farmer_details']);


        $farmer_id = $farmerDetails->id;

        foreach ($farm_details as $farmDetailData) {
            // Add 'farmer_id' to each farm detail
            $farmDetailData['farmer_id'] = $farmer_id;
            $farmDetailData['saved_by_name'] = $details->name;
            $farmDetailData['saved_by_id'] = $details->id;
            $farmDetailData['updated_by_name'] = "";
            $farmDetailData['updated_by_id'] = "";

            // Create the farm detail
            FarmDetails::create($farmDetailData);
        }

        // check relation working
        $db = FarmerDetails::where('id', $farmer_id)->with('FarmInfo')->get();
        return $db;
    }

    // get address detail from postal code api
    public function getPostalAddress(Request $request)
    {
        $postcode = $request->postcode;

        $getZone = '';
        $pin = file_get_contents('https://api.postalpincode.in/pincode/' . $postcode);
        $pins = json_decode($pin);
        foreach ($pins as $key) {
            if ($key->PostOffice == null) {
                $response['success'] = false;
                $response['error_message'] = "Can not fetch postal address please try again";
                $response['error'] = true;
            } else {
                $arr['city'] = $key->PostOffice[0]->Block;
                $arr['district'] = $key->PostOffice[0]->District;
                $arr['state'] = $key->PostOffice[0]->State;

                $response['success'] = true;
                $response['success_message'] = "Postal Address fetch successfully";
                $response['error'] = false;
                $response['data'] = $arr;
                $response['zone'] = $getZone;
            }
        }
        return response()->json($response);
    }

    public function export_farmer_details()
    {
        // $biometric_export = new FarmerDetails();
        //        $collection = $biometric_export->collection();
        //     return $collection;
        return Excel::download(new ExportFarmerDetail(), 'Farmers.xlsx');
    }

    public function districtDetails(Request $request)
    {
        // return "DS";
        $subdistrict = $request->input('subdistrict');
        $state = $request->input('state');
        // return [$state,$subdistrict];

        $district_details = LocationData::where('subdistrict_name', $subdistrict)
            ->where('state_name', $state)
            ->get();

        if (!empty($district_details)) {

            $response['success'] = true;
            $response['error_message'] = "District Fetch";
            $response['error'] = true;
            $response['district_details'] = $district_details;
            $response['statuscode'] = '200';
            $response['msg'] = 'Districts Fetched Successfully';
            return response()->json($response);
        }

        $response['success'] = false;
        $response['success_message'] = 'Not Found';
        $response['error'] = false;
        $response['statuscode'] = '200';


        return response()->json($response);
    }
}
