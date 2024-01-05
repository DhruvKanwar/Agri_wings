<?php

namespace App\Http\Controllers;

use App\Imports\BulkImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

date_default_timezone_set('Asia/Kolkata');
ini_set('max_execution_time', -1);

class ImportExportController extends Controller
{
    //
    public function ShowImportExcel()
    {
        return view('pages.import-data');
    }

    public function ImportExcel(Request $request)
    {

        $data=$request->all();
        // return $data;
        if ($data['selected_option'] == 1) {
            try {
                $type = $data['selected_option'];
                // echo'<pre>'; print_r($_FILES); die;
                $data = Excel::import(new BulkImport, request()->file('file'));
                $response['success'] = true;
                $response['import_type'] = $type;
                $response['messages'] = 'Succesfully imported';
                return $response;
            } catch (\Exception $e) {
                $response['success'] = false;
                $response['messages'] = 'something wrong';
                // echo'<pre>'; print_r($e); die;
              return $e;
            }
        }
        return view('pages.import-data');
    }
}
