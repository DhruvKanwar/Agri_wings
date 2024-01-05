<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ExportFarmerDetail implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection(): Collection
    {
        // return 1;
        $data = DB::table('farmer_details')->get();
        // dd($data);
        // return $data;
        $size = sizeof($data);
        // $val="";
        $arr_instrulist_excel[] = array();

        for ($i = 0; $i < $size; $i++) {
            $id = $data[$i]->id;
           

            $arr_instrulist_excel[] = array(
                'id'  => $id,
                'name' => $data[$i]->farmer_name,
                'mobile' => $data[$i]->farmer_mobile_no,
                'sub_district' => $data[$i]->farmer_sub_district,
                'village' => $data[$i]->farmer_village,
                'district' => $data[$i]->farmer_district,
                'state' => $data[$i]->farmer_state,
                'pincode' => $data[$i]->farmer_pincode,
                'address' => $data[$i]->farmer_address,
                

            );

            // echo"<pre>";
            // return[$id,$saved_by_name,$date1,$time1,$updated_by_name,$date2,$time2];
            // print_r($data[$i]);
        }
        // exit;
        return collect($arr_instrulist_excel);

        // return Tercourier::select('id','saved_by_name','created_at','updated_by_name','updated_at')->get();
    }

    public function headings(): array
    {
        return [
            "S.No.", "Name", "Mobile No","Sub District", "Village", "District", "State", "Pincode","Address"
        ];
    }
}
