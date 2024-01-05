<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PilotController extends Controller
{
    //
    public function show_pilot_list()
    {
        // $farmDetailData = FarmerDetails::with('FarmInfo')->get();

        return view('pilots.pilot-list');

        // return view('farmers.farmer-list', ['farmer_details' => $farmDetailData]);
    }

    public function add_pilot()
    {
        return view('pilots.create_new_pilot');
    }

}
