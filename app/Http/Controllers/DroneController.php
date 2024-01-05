<?php

namespace App\Http\Controllers;

use App\Models\DroneDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DroneController extends Controller
{
    //
    public function show_drone_list()
    {

        $drone_details = DroneDetails::get();

        return view('drones.drone-list', ['drone_details' => $drone_details]);
        // return view('drones.drone-list');

        // return view('farmers.farmer-list', ['farmer_details' => $farmDetailData]);
    }

    public function add_drone()
    {
       
        return view('drones.create_new_drone');
    }

    public function submit_drone_details(Request $request)
    {
        $data = $request->all();
        // return $data;
        $request->validate([
            'drone_details.capacity' => 'numeric',
            'drone_details.mfg_year' => 'required|numeric',
            'drone_details.model' => 'required|string|max:255',
            'drone_details.uin' => 'required|string|max:255',
        ]);
    
        $details = Auth::user();

        $data['drone_details']['saved_by_name'] = $details->name;
        $data['drone_details']['saved_by_id'] = $details->id;
        $data['drone_details']['updated_by_name'] = "";
        $data['drone_details']['updated_by_id'] = "";
        // return $data;
        // Create or update the farmer details
        $drone_id = DroneDetails::select('drone_id')->latest('drone_id')->first();
        $drone_id = json_decode(json_encode($drone_id), true);
        if (empty($drone_id) || $drone_id == null) {
            $initial_number = "Drone-1000001";
            $data['drone_details']['drone_id'] = $initial_number;
        } else {
            $parts = explode('-', $drone_id['drone_id']);

            // Extract the numeric part and increment it
            $next_number = (int)$parts[1] + 1;

            // Concatenate the string and the incremented number
            $next_drone_id = $parts[0] . '-' . $next_number;
            $data['drone_details']['drone_id'] = $next_drone_id['drone_id'] + 1;
        }

        $droneDetails = DroneDetails::create($data['drone_details']);


    return 1;
    }
}
