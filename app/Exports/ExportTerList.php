<?php

namespace App\Exports;

use App\Models\Ter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ExportTerList implements FromCollection, WithHeadings
{
    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {

        // start
        $data  = Ter::with('operatorReimbursement', 'assetOperator')
        ->where(function ($query) {
            $query->where('from_date', '>=', $this->fromDate)
            ->where('from_date', '<=', $this->toDate);
        })
        ->orWhere(function ($query) {
            $query->where('to_date', '>=', $this->fromDate)
            ->where('to_date', '<=', $this->toDate);
        })
        ->get();
        // end
   
        // dd($data);
        // return $data;
        $size = sizeof($data);
        // $val="";
        $arr_instrulist_excel[] = array();

        foreach ($data as $item) {
            $status='';
            if($item->status == 1)
            {
                $status='Created';
            } else if ($item->status == 2) {
                $status = 'Approved';
            }else if ($item->status == 3) {
                $status = 'Rejected';
            }
            $operatorReimbursements = $item['operator_reimbursement'];
            return $operatorReimbursements;
            // Iterate over each operator_reimbursement
            foreach ($operatorReimbursements as $reimbursement) {
            
                // Access the details of each operator_reimbursement
                $id = $reimbursement['id'];
                $unid = $reimbursement['unid'];
                $category = $reimbursement['category'];
                $billNo = $reimbursement['bill_no'];
                // Access other properties as needed
            }
            
            $arr_instrulist_excel[] = [
                'unid' => $item->id,
                'operator_code' => $item->assetOperator->code,
                'operator_name' => $item->assetOperator->name,
                'operator_phone' => $item->assetOperator->phone,
                'from_date' => $item->from_date,
                'to_date' => $item->to_date,
                'bill_amount' => $item->operatorReimbursement->bill_amount, 
                'claimed_amount' => $item->operatorReimbursement->claimed_amount, 
                'category' => $item->operatorReimbursement->category, 
                'bill_number' => $item->operatorReimbursement->bill_no, 
                'remarks' => $item->operatorReimbursement->remarks, 
                'attachment' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/reimburse/'.$item->operatorReimbursement->attachment, 
                'da_amount' => $item->da_amount,
                'da_limit' => $item->da_limit,
                'total_attendance' => $item->total_attendance,
                'hr_update_date' => $item->hr_updated_date,
                'submit_date' => $item->submit_date,
                'status' => $status,
            ];
        }

        return collect($arr_instrulist_excel);

        // return Tercourier::select('id','saved_by_name','created_at','updated_by_name','updated_at')->get();
    }

    public function headings(): array
    {
        return [
            "S.No.", "Name", "Mobile No", "Sub District", "Village", "District", "State", "Pincode", "Address"
        ];
    }
}
