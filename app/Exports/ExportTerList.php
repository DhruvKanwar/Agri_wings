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
        $data = Ter::with('operatorReimbursement', 'assetOperator')
        ->where(function ($query) {
            $query->where('from_date', '>=', $this->fromDate)
                ->where('from_date', '<=', $this->toDate);
        })
            ->orWhere(function ($query) {
                $query->where('to_date', '>=', $this->fromDate)
                    ->where('to_date', '<=', $this->toDate);
            })
            ->get();

        $arr_instrulist_excel = [];

        foreach ($data as $item) {
            foreach ($item->operatorReimbursement as $reimbursement) {
                $status = '';
                if ($item->status == 1) {
                    $status = 'Created';
                } else if ($item->status == 2) {
                    $status = 'Approved';
                } else if ($item->status == 3) {
                    $status = 'Rejected';
                }

                $arr_instrulist_excel[] = [
                    'unid' => $item->id,
                    'operator_code' => $item->assetOperator->code,
                    'operator_name' => $item->assetOperator->name,
                    'operator_phone' => $item->assetOperator->phone,
                    'from_date' => $reimbursement->from_date,
                    'to_date' => $reimbursement->to_date,
                    'bill_amount' => $reimbursement->bill_amount,
                    'claimed_amount' => $reimbursement->claimed_amount,
                    'category' => $reimbursement->category,
                    'bill_number' => $reimbursement->bill_no,
                    'remarks' => $reimbursement->remarks,
                    'attachment' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/reimburse/' . $reimbursement->attachment,
                    'da_amount' => $item->da_amount,
                    'da_limit' => $item->da_limit,
                    'total_attendance' => $item->total_attendance,
                    'hr_update_date' => $item->hr_updated_date,
                    'submit_date' => $item->submit_date,
                    'status' => $status,
                ];
            }
        }

        return collect($arr_instrulist_excel);
    }

    public function headings(): array
    {
        return [
            "UNID", "Operator Code", "Operator Name", "Operator Phone", "From_Date", "To_Date", "Bill Amount", "Claimed Amount", "Category",
            "Bill Number", "Remarks", "Attachment", "Da Amount", "Da Limit", "Total Attendance", "Hr Update Date", "Submit Date", "Status"
        ];
    }
}
