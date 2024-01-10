<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FarmerDetails;
use App\Models\FarmDetails;
use App\Exports\ExportFarmerDetail;
use App\Models\FarmerProfile;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LocationData;
use Illuminate\Support\Facades\Validator;
use DB;

class FarmerController extends Controller
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
            return ['statuscode' => '200', 'msg' => 'Location Not Found...'];
        }
    }

    public function submit_farmer_details(Request $request)
    {
        $data = $request->all();
        // return $data;
        // $jsonPayload = json_encode($data, JSON_PRETTY_PRINT);

        // // Print the JSON payload
        // echo $jsonPayload;
        // exit;
        $validator = Validator::make($request->all(), [
            'farmer_details' => 'required|array',
            'farmer_details.farm_addresses' => 'required|array|min:1',
            'farmer_details.farmer_name' => 'required|string|max:255',
            'farmer_details.farmer_mobile_no' => 'required|string|max:15',
            'farmer_details.farmer_pincode' => 'required|string|max:10',
            'farmer_details.farmer_district' => 'required|string|max:255',
            'farmer_details.farmer_state' => 'required|string|max:255',
            'farmer_details.farmer_village' => 'required|string|max:255',
            'farmer_details.farmer_sub_district' => 'required|string|max:255',
            'farmer_details.farmer_address' => 'required|string',

            'farmer_details.profile' => 'nullable|array|min:1',
            'farmer_details.profile.*.gender' => 'nullable|string|max:255',
            'farmer_details.profile.*.income' => 'nullable|string|max:255',
            'farmer_details.profile.*.education_level' => 'nullable|string|max:255',
            'farmer_details.profile.*.date_of_birth' => 'nullable|date',
            'farmer_details.profile.*.wedding_anniversary' => 'nullable|date',
            'farmer_details.profile.*.attitude' => 'nullable|string|max:255',
            'farmer_details.profile.*.lifestyle' => 'nullable|string|max:255',
            'farmer_details.profile.*.professional_info' => 'nullable|string|max:255',
            'farmer_details.profile.*.influence' => 'nullable|string|max:255',
            'farmer_details.profile.*.hobbies' => 'nullable|string|max:255',
            'farmer_details.profile.*.favourite_activities' => 'nullable|string|max:255',
            'farmer_details.profile.*.intrests' => 'nullable|string|max:255',
            'farmer_details.profile.*.mobile_phone_used' => 'nullable|string|max:255',
            'farmer_details.profile.*.social_media_platform' => 'nullable|string|max:255',
            'farmer_details.profile.*.tech_proficiency' => 'nullable|string|max:255',
            'farmer_details.profile.*.preferred_communication' => 'nullable|string|max:255',
            'farmer_details.profile.*.email_id' => 'nullable|email|max:255',
            'farmer_details.profile.*.ratings' => 'nullable|string|max:255',
            'farmer_details.profile.*.suggestion_for_improvement' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $farm_details = $data['farmer_details']['farm_addresses'];

        unset($data['farmer_details']['farm_addresses']);
        $details = Auth::user();

        $data['farmer_details']['saved_by_name'] = $details->name;
        $data['farmer_details']['saved_by_id'] = $details->id;
        $data['farmer_details']['updated_by_name'] = "";
        $data['farmer_details']['updated_by_id'] = "";
        // return $data;

        // start sate code
        $inputState =   $data['farmer_details']['farmer_state'];

        $stateName = strtoupper($inputState); // Convert to uppercase to match the state codes in uppercase

        // Generate the state code based on the state name
        $stateCode = $this->generateStateCode($stateName);


        // Query the database to get the latest farmer code for the state
        $latestCode = FarmerDetails::where('farmer_code', 'like', "AWF$stateCode%")
            ->orderBy('farmer_code', 'desc')
            ->value('farmer_code');

        // Generate the new farmer code
        if ($latestCode) {
            $data['farmer_details']['farmer_code'] = $this->generateFarmerCodeFromLatest($stateCode, $latestCode);
        } else {
            $data['farmer_details']['farmer_code'] = "AWF$stateCode-0001";
        }

        // end state code

        // print_r($data['farmer_details']['farmer_code']);
        // exit;

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

        $profileData = $request->input('farmer_details.profile')[0]; // Assuming only one profile is submitted
        $profileData['farmer_id'] = $farmer_id;
        // return $profileData;
        $farmerProfile = FarmerProfile::create($profileData);
        // check relation working
        $farmer_data = FarmerDetails::where('id', $farmer_id)
            ->with(['FarmInfo', 'FarmerProfileInfo']) // Include FarmInfo and nested FarmerProfile
            ->get();
        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Farmer Details Stored Successfully...',
            'farmerdata' => $farmer_data
        );
        return response()->json($result_array, 200);
    }

    public function edit_farmer_details(Request $request)
   {
        $data = $request->all();
        // return $data;
        // $jsonPayload = json_encode($data, JSON_PRETTY_PRINT);

        // // Print the JSON payload
        // echo $jsonPayload;
        // exit;
        $validator = Validator::make($request->all(), [
            'farmer_details' => 'required|array',
            'farmer_details.farm_addresses' => 'required|array|min:1',
            'farmer_details.farmer_name' => 'required|string|max:255',
            'farmer_details.farmer_mobile_no' => 'required|string|max:15',
            'farmer_details.farmer_pincode' => 'required|string|max:10',
            'farmer_details.farmer_district' => 'required|string|max:255',
            'farmer_details.farmer_state' => 'required|string|max:255',
            'farmer_details.farmer_village' => 'required|string|max:255',
            'farmer_details.farmer_sub_district' => 'required|string|max:255',
            'farmer_details.farmer_address' => 'required|string',

            'farmer_details.profile' => 'nullable|array|min:1',
            'farmer_details.profile.*.gender' => 'nullable|string|max:255',
            'farmer_details.profile.*.income' => 'nullable|string|max:255',
            'farmer_details.profile.*.education_level' => 'nullable|string|max:255',
            'farmer_details.profile.*.date_of_birth' => 'nullable|date',
            'farmer_details.profile.*.wedding_anniversary' => 'nullable|date',
            'farmer_details.profile.*.attitude' => 'nullable|string|max:255',
            'farmer_details.profile.*.lifestyle' => 'nullable|string|max:255',
            'farmer_details.profile.*.professional_info' => 'nullable|string|max:255',
            'farmer_details.profile.*.influence' => 'nullable|string|max:255',
            'farmer_details.profile.*.hobbies' => 'nullable|string|max:255',
            'farmer_details.profile.*.favourite_activities' => 'nullable|string|max:255',
            'farmer_details.profile.*.intrests' => 'nullable|string|max:255',
            'farmer_details.profile.*.mobile_phone_used' => 'nullable|string|max:255',
            'farmer_details.profile.*.social_media_platform' => 'nullable|string|max:255',
            'farmer_details.profile.*.tech_proficiency' => 'nullable|string|max:255',
            'farmer_details.profile.*.preferred_communication' => 'nullable|string|max:255',
            'farmer_details.profile.*.email_id' => 'nullable|email|max:255',
            'farmer_details.profile.*.ratings' => 'nullable|string|max:255',
            'farmer_details.profile.*.suggestion_for_improvement' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $farm_details = $data['farmer_details']['farm_addresses'];

        unset($data['farmer_details']['farm_addresses']);
        $details = Auth::user();

  
        $data['farmer_details']['updated_by_name'] = "$details->name;";
        $data['farmer_details']['updated_by_id'] = "$details->id";

  $farmer_id= $data['farmer_details']['farmer_id'];
        unset($data['farmer_details']['farmer_id']);
        $farmer_profile= $data['farmer_details']['profile'][0];
        unset($data['farmer_details']['profile']);
        // return $data['farmer_details'];
        $farmerDetails = FarmerDetails::where('id', $farmer_id)->update($data['farmer_details']);


        // $farmer_id = $farmerDetails->id;

        $existingFarmDetails = DB::table('farm_details')->where('farmer_id', $farmer_id)->get();


        // Loop through the submitted farm details
        foreach ($existingFarmDetails as $submittedFarmDetail) {
            // Check if the farm detail has an 'id'
            // return $submittedFarmDetail;
            DB::table('farm_details')->where('id', $submittedFarmDetail['id'])->update($submittedFarmDetail);
        }

        return 1;

        // Get the existing profile associated with the given farmer_id
        $existingProfile = FarmerProfile::where('farmer_id', $farmer_id)->first();

        // Check if the submitted profile has an 'id'
        if (isset($farmer_profile['id'])) {
            // If 'id' is present, update the existing profile
            if ($existingProfile) {
                $existingProfile->update($farmer_profile);
            }
        }
        // check relation working
        $farmer_data = FarmerDetails::where('id', $farmer_id)
            ->with(['FarmInfo', 'FarmerProfileInfo']) // Include FarmInfo and nested FarmerProfile
            ->get();
        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Farmer Details Updated Successfully...',
            'farmerdata' => $farmer_data
        );
        return response()->json($result_array, 200);
   }

    private function generateFarmerCodeFromLatest($stateCode, $latestCode)
    {
        // Extract the numeric part and increment
        $numericPart = (int)substr($latestCode, -4) + 1;

        // Generate the new farmer code
        $newCode = "AWF" . $stateCode . "-" . str_pad($numericPart, 4, '0', STR_PAD_LEFT);

        return $newCode;
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
