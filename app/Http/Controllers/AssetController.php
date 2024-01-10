<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    //
    public function show_asset_list()
    {

        $asset_details = AssetDetails::get();

        return view('assets.assets-list', ['asset_details' => $asset_details]);
        // return view('drones.drone-list');

        // return view('farmers.farmer-list', ['farmer_details' => $farmDetailData]);
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

 

     
        $details = Auth::user();
      

        $data['asset_details']['saved_by_name'] = $details->name;
        $data['asset_details']['saved_by_id'] = $details->id;
        $data['asset_details']['updated_by_name'] = "";
        $data['asset_details']['updated_by_id'] = "";
        $data['asset_details']['asset_name'] = 'Drone';
        // return $data;
        // Create or update the farmer details
        $asset_id = AssetDetails::select('asset_id')->latest('asset_id')->first();
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


    if($AssetDetails)
    {
            $response['success'] = true;
            $response['statuscode'] = '200';
            $response['msg'] = 'Assest Added Successfully...';
            return response()->json($response);
    }else{
            $response['success'] = false;
            $response['statuscode'] = '403';
            $response['msg'] = 'There is server problem. Record Not Saved.';
            return response()->json($response);
    }
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
