<?php

namespace App\Http\Controllers;

use App\Models\Services;
use Illuminate\Http\Request;

class MisController extends Controller
{
    public function download_service_report()
    {
        $data = Services::with(['assetOperator', 'orderTimeline', 'asset', 'clientDetails', 'farmerDetails', 'farmLocation'])->get();

        // return $data[0]->orderTimeline;
        $arr_instrulist_excel = [];

        foreach ($data as $item) {
            // foreach ($item->operatorReimbursement as $reimbursement) {
            $status = '';
            if ($item->status == 1) {
                $status = 'Created';
            } else if ($item->status == 2) {
                $status = 'Approved';
            } else if ($item->status == 3) {
                $status = 'Rejected';
            }

            $order_status = "";
            if ($item->order_status == 0) {
                $order_status = "cancel";
            } elseif ($item->order_status == 1) {
                $order_status = "created";
            } elseif ($item->order_status == 2) {
                $order_status = "assigned";
            } elseif ($item->order_status == 3) {
                $order_status = "accepted";
            } elseif ($item->order_status == 4) {
                $order_status = "started";
            } elseif ($item->order_status == 5) {
                $order_status = "completed";
            } elseif ($item->order_status == 6) {
                $order_status = "delivered";
            }


            $type = "";

            if ($item->order_type == 1) {
                $type = "general";
            } elseif ($item->order_type == 2) {
                $type = "Client";
            } elseif ($item->order_type == 3) {
                $type = "Subvention";
            } elseif ($item->order_type == 4) {
                $type = "R & D";
            } elseif ($item->order_type == 5) {
                $type = "Demo";
            } else {
                // Default type or handle invalid order types
                $type = "Unknown";
            }


            if (!empty($item->sprayed_acreage)) {
                $base_price = round($item->total_amount / $item->sprayed_acreage, 2);
            } else {
                $base_price = round($item->total_amount / $item->requested_acreage, 2); // Rounded to 2 decimal places
            }

            $live_host_name = request()->getHttpHost();
            // return $live_host_name;
            // || $live_host_name == "ter.etsbeta.com"
            if ($item->order_status == 6) {
                $invoice_link = 'http:://' . $live_host_name . '/zp/' . base64_encode($item->id);;
            } else {
                $invoice_link = "";
            }


            $arr_instrulist_excel[] = [
                's_no' => $item->order_id,
                'client_name' => $item->clientDetails->regional_client_name,
                'sr_booking' => $item->order_date,
                'sr_type' => $type,
                'sr_date' =>  $item->spray_date,
                'farmer_mobile_no' => $item->farmerDetails->farmer_mobile_no,
                'farmer_name' => $item->farmerDetails->farmer_name,
                'farmer_location' => $item->farmerDetails->farmer_address,
                'farmer_sub_district' => $item->farmerDetails->farmer_sub_district,
                'farmer_district' => $item->farmerDetails->farmer_district,
                'farmer_state' => $item->farmerDetails->farmer_state,
                'crop_name' => $item->crop_name,
                'farm_location' => $item->farmLocation->address,
                'requested_acreage' => $item->requested_acreage,
                'actual_acreage' => $item->sprayed_acreage,
                'base_price' => $base_price,
                'total_discount' => $item->total_discount,
                'total_service_value' => $item->total_amount,
                'asset_code' => $item->asset->asset_id,
                'asset_operator' => $item->assetOperator->name,
                'ao_mobile_no' => $item->assetOperator->phone,
                'service_status' => $order_status,
                'actual_service_date' =>   $item->delivery_date,

                // start
                // 'amount_recieved' =>   $item->amount_recieved,
                // 'transaction_id' =>   $item->transaction_id,
                // 'payment_status' =>   $item->payment_status,
                // end

                'service_request_by' =>   $item->orderTimeline->created_by,
                'invoice_link' => $invoice_link,

                // start
                // 'payment_image' => $item->payment_image,
                // end

                'farm_image' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/farmer_img_/' . $item->orderTimeline->farmer_image,

                // start
                // 'sign_image' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/sign_img/' . $item->orderTimeline->farmer_image,
                // end
                'noc' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/noc_image/' . $item->orderTimeline->noc_image,
                'coordinates' => $item->FarmLocation->location_coordinates,

            ];
            // }
        }

        return collect($arr_instrulist_excel);
    }
}
