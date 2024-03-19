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


            $amount_received = json_decode($item->amount_received);
            $payment_data_array = [];
            $payment_data_str = ''; // Define $payment_data_str here

            // Check if $amount_received is not empty
            if (!empty($amount_received)) {
                foreach ($amount_received as $amount_data) {
                    // Check if reference_no property exists before accessing it
                    if (isset($amount_data->reference_no)) {
                        // Concatenate payment data
                        $payment_data = $amount_data->reference_no . ' - ' . 'Rs. ' . $amount_data->amount;
                        $payment_data_array[] = $payment_data;
                    }
                }
                // Concatenate payment data strings
                $payment_data_str = implode(', ', $payment_data_array);
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

            if(!empty($item->refund_image))
            {
                $refund_image = 'https://agriwingsnew.s3.us-east-2.amazonaws.com/refund_img_/'. $item->refund_image;
            }else{
                $refund_image='';
            }

            // return $item->amount_recieved;

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


            if(!empty($item->orderTimeline->farmer_signature))
            {
                $sign_status='Signed';
            }else{
                $sign_status = '';
            }

            if ($item->orderTimeline->farmer_available == 1) {
                $available_person_name = 'Self';
                $available_person_phone = 'Self';

            } else {
                $available_person_name = $item->orderTimeline->available_person_name;
                $available_person_phone = $item->orderTimeline->available_person_phone;
            }

            if ($item->payment_status == 1) {
                $payment_status = 'Paid';
            } else {
                $payment_status = 'Pending';
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
                'battery' => $item->battery_ids,
                'service_status' => $order_status,
                'actual_service_date' =>   $item->delivery_date,

                // start
                'amount_recieved' =>   $payment_data_str,
                // 'transaction_id' =>   $item->transaction_id,
                'payment_status' =>   $payment_status,
                //complete,refund,unpaid,partially paid
                // end

                'service_request_by' =>   $item->orderTimeline->created_by,
                'invoice_link' => $invoice_link,

                // start
                'payment_image' => $refund_image,
                // only in refund
                // end

                'farm_image' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/farmer_img_/' . $item->orderTimeline->farmer_image,

                // start
                'sign_image' => $sign_status,
                // end
                'noc' => 'https://agriwingsnew.s3.us-east-2.amazonaws.com/noc_image/' . $item->orderTimeline->noc_image,
                'coordinates' => $item->FarmLocation->location_coordinates,
                'available_person_name' => $available_person_name,
                'available_person_phone' => $available_person_phone,



            ];
            // }
        }

        return collect($arr_instrulist_excel);
    }
}
