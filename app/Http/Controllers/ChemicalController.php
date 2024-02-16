<?php

namespace App\Http\Controllers;

use App\Imports\BulkImport;
use App\Models\Chemical;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ChemicalController extends Controller
{
    //

    public function import_chemical(Request $request)
    {
        // Validate the uploaded file
        // $request->validate([
        //     'import_file' => 'required|mimes:csv,txt|max:2048' // Assuming only CSV and TXT files are allowed
        // ]);

        // Process the uploaded file
        if ($request->hasFile('import_file')) {
            $_POST['selected_option']=3;
            $data = Excel::import(new BulkImport, request()->file('import_file'));
            $response['status'] = 'success';
            $response['statuscode'] = '200';
            $response['messages'] = 'Succesfully imported';
            return $response;
        }

        // Handle the case where no file was uploaded
        // return redirect()->back()->with('error', 'No file uploaded.');
    }

    public function get_chemical_list()
    {
        $chemical_list = Chemical::select('id','chemical_name')->where('status', 1)->get();
        return response()->json(['msg' => 'Chemicals List Fetched Successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $chemical_list], 201);

    }
}
