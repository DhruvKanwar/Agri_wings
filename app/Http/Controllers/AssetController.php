<?php

namespace App\Http\Controllers;

use App\Models\AssetDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
        $request->validate([
            'asset_details.capacity' => 'numeric',
            'asset_details.mfg_year' => 'required|numeric',
            'asset_details.model' => 'required|string|max:255',
            'asset_details.uin' => 'required|string|max:255',
        ]);
    
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
            $initial_number = "Asset-1000001";
            $data['asset_details']['asset_id'] = $initial_number;
        } else {
            $parts = explode('-', $asset_id['asset_id']);

            // Extract the numeric part and increment it
            $next_number = (int)$parts[1] + 1;

            // Concatenate the string and the incremented number
            $next_asset_id = $parts[0] . '-' . $next_number;
            $data['asset_details']['asset_id'] = $next_asset_id['asset_id'] + 1;
        }

        $AssetDetails = AssetDetails::create($data['asset_details']);


    return 1;
    }
}
