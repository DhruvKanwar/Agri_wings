<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\LocationData;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BulkImport implements ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
       
        if ($_POST['selected_option'] == 1) {
            // echo "<pre>";
            // print_r($row['state_name']);

            // exit;
          

            return new LocationData([
                'state_name' => $row['state_name'],
                'state_code' => $row['state_code'],
                'district_name' => $row['district_name'],
                'district_code' => $row['district_code'],
                'subdistrict_name' => $row['subdistrict_name'],
                'subdistrict_code' => $row['subdistrict_code'],
                'vil_town_name' => $row['vil_town_name'],
                'vil_town_code' => $row['vil_town_code'],

            ]);
         
            // die;
            // $for = DB::table('for_companies')
            //     ->where('for_company', '=', $row['for_company'])
            //     ->first();
            // if (is_null($for)) {
            //     return new ForCompany([
            //         'for_company'  => $row['for_company']
            //     ]);
            // }
        }
    }
}
