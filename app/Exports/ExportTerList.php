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
            $status = '';
            if ($item->status == 1) {
                $status = 'Created';
            } else if ($item->status == 2) {
                $status = 'Approved';
            } else if ($item->status == 3) {
                $status = 'Rejected';
            }

            $operatorCode = $operatorName = $operatorPhone = '';
            if ($item->assetOperator) {
                $operatorCode = $item->assetOperator->code;
                $operatorName = $item->assetOperator->name;
                $operatorPhone = $item->assetOperator->phone;
            }

            $billAmount = $claimedAmount = $category = $billNumber = $remarks = $attachment = '';
            if ($item->operatorReimbursement) {
                $billAmount = $item->operatorReimbursement->bill_amount;
                $claimedAmount = $item->operatorReimbursement->claimed_amount;
                $category = $item->operatorReimbursement->category;
                $billNumber = $item->operatorReimbursement->bill_no;
                $remarks = $item->operatorReimbursement->remarks;
                $attachment = 'https://agriwingsnew.s3.us-east-2.amazonaws.com/reimburse/' . $item->operatorReimbursement->attachment;
            }

            $arr_instrulist_excel[] = [
                'unid' => $item->id,
                'operator_code' => $operatorCode,
                'operator_name' => $operatorName,
                'operator_phone' => $operatorPhone,
                'from_date' => $item->from_date,
                'to_date' => $item->to_date,
                'bill_amount' => $billAmount,
                'claimed_amount' => $claimedAmount,
                'category' => $category,
                'bill_number' => $billNumber,
                'remarks' => $remarks,
                'attachment' => $attachment,
                'da_amount' => $item->da_amount,
                'da_limit' => $item->da_limit,
                'total_attendance' => $item->total_attendance,
                'hr_update_date' => $item->hr_updated_date,
                'submit_date' => $item->submit_date,
                'status' => $status,
            ];
        }

        return collect($arr_instrulist_excel);
    }

    public function headings(): array
    {
        return [
            "S.No.", "Name", "Mobile No", "Sub District", "Village", "District", "State", "Pincode", "Address"
        ];
    }
}
