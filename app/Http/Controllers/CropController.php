<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use Illuminate\Http\Request;
use App\Models\CropPrice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class CropController extends Controller
{

    public function get_crops()
    {
        try {
            // You can add any conditions or filters based on your requirements
            $cropPrices = Crop::all();

            return response()->json([
                'msg' => 'Crops fetched successfully',
                'data' => $cropPrices,
                'statuscode' => '200',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => 'Error fetching crops',
                'statuscode' => '500',
                'status' => 'error',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function get_crop_price_list(Request $request)
    {
        try {
            // You can add any conditions or filters based on your requirements
            $cropPrices = Crop::select('id', 'crop_name', 'base_price')->where('base_price','!=','' )->get();



            return response()->json([
                'msg' => 'Crop prices fetched successfully',
                'data' => $cropPrices,
                'statuscode' => '200',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => 'Error fetching crop prices',
                'statuscode' => '500',
                'status' => 'error',
                'error' => $e->getMessage()
            ]);
        }
    }


    public function get_crop_details(Request $request)
    {
        try {
            $data = $request->all();
            $crop_id = $data['crop_id'];
            // You can add any conditions or filters based on your requirements
            $cropPrices['availability'] = CropPrice::select('id', 'crop_id', 'crop_name', 'state', 'state_price')->where('crop_id', $crop_id)->get();

            $cropPrices['crop_details'] = Crop::select('crop_name', 'base_price')->where('id', $crop_id)->get();
            // return $cropPrices;
            return response()->json([
                'msg' => 'Crop price Details fetched successfully',
                'data' => $cropPrices,
                'statuscode' => '200',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => 'Error fetching crop prices',
                'statuscode' => '500',
                'status' => 'error',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function submit_crop_prices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cropData.crop_id' => 'required|integer',
            'cropData.crop_name' => 'required|string',
            'cropData.base_price' => 'required|numeric',
            'cropData.availability.*.state' => 'nullable|string',
            'cropData.availability.*.state_price' => 'nullable|numeric',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors(), 'statuscode' => '400', 'status' => 'error'], 422);
        }

        $data = $request->all();
        $cropData = $data['cropData'];

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // Create or update availability records
            $insertedRecords = [];
            foreach ($cropData['availability'] as $availabilityData) {
                $state = $availabilityData['state'];
                $state_price = $availabilityData['state_price'];

                $existingAvailability = CropPrice::where('crop_id', $cropData['crop_id'])
                    ->where('state', $state)
                    ->first();

                $existingCrop = Crop::where('id', $cropData['crop_id'])
                    ->first();

                if (empty($existingCrop)) {
                    DB::rollBack();

                    return response()->json([
                        'msg' => 'Crop does not exists for Crop ID ' . $cropData['crop_id'],
                        'data' => $existingCrop,
                        'statuscode' => '400',
                        'status' => 'error'
                    ]);
                }

                if ($existingAvailability) {
                    // Rollback the transaction if record already exists
                    DB::rollBack();

                    return response()->json([
                        'msg' => 'Availability record already exists with the specified crop_id and state',
                        'data' => $existingAvailability,
                        'statuscode' => '400',
                        'status' => 'error'
                    ]);
                } else {
                    $details = Auth::user();

                    if(!empty($state_price))
                    {
                        $cropinsertData = [
                            'crop_id' => $cropData['crop_id'],
                            'crop_name' => $cropData['crop_name'],
                            'state' => $state,
                            'state_price' => $state_price,
                            'saved_by_name' => $details->name,
                            'saved_by_id' => $details->id,
                        ];

                        $insertedRecord = CropPrice::create($cropinsertData);
                        $crop_base_price = Crop::where('id', $cropData['crop_id'])->update(['base_price' => $cropData['base_price']]);
                        $insertedRecords[] = $insertedRecord;
                    }else{
                        $cropinsertData = [
                            'crop_id' => $cropData['crop_id'],
                            'crop_name' => $cropData['crop_name'],
                            'state' => $state,
                            'state_price' => "",
                            'saved_by_name' => $details->name,
                            'saved_by_id' => $details->id,
                        ];

                        $insertedRecord = CropPrice::create($cropinsertData);
                        $crop_base_price = Crop::where('id', $cropData['crop_id'])->update(['base_price' => $cropData['base_price']]);
                        $get_crop_insert_data=Crop::where('id', $cropData['crop_id'])->get();
                        $insertedRecords[] = $get_crop_insert_data;

                    }
                   

                  
                    // return $cropData['base_price'];
                   
                }
            }

            // Commit the transaction after processing all records
            DB::commit();

            return response()->json([
                'msg' => 'Crop data stored successfully',
                'statuscode' => '200',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            // Something went wrong, rollback the transaction
            DB::rollBack();

            return response()->json([
                'msg' => 'Error storing crop data',
                'statuscode' => '500',
                'status' => 'error',
                'error' => $e->getMessage()
            ]);
        }
    }


    // public function submit_crop_prices(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'cropData.crop_id' => 'required|integer',
    //         'cropData.crop_name' => 'required|string',
    //         'cropData.base_price' => 'required|numeric',
    //         'cropData.availability.*.state' => 'nullable|string',
    //         'cropData.availability.*.state_price' => 'nullable|numeric',
    //     ]);

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json(['msg' => $validator->errors(), 'statuscode' => '400', 'status' => 'error',], 422);
    //     }
    //     $data = $request->all();

    //     // $cropData = $data['cropData']['availability'][0]['state'];
    //     $cropData = $data['cropData'];


    //     // Create or update availability records
    //     foreach ($cropData['availability'] as $availabilityData) {
    //         $state = $availabilityData['state'];
    //         $state_price = $availabilityData['state_price'];

    //         $existingAvailability = CropPrice::where('crop_id',$cropData['crop_id'])
    //         ->where('state', $state)
    //         ->first();



    //         if($existingAvailability)
    //         {
    //             return response()->json([
    //                 'msg' => 'Availability record already exists with the specified crop_id and state',
    //                 'data' => $existingAvailability,
    //                 'statuscode' => '400',
    //                 'status' => 'error'
    //             ]); 
    //         }else{

    //             $cropinsertData['crop_id'] = $cropData['crop_id'];
    //             $cropinsertData['crop_name'] = $cropData['crop_name'];
    //             $cropinsertData['base_price'] = $cropData['base_price'];
    //             $cropinsertData['state'] = $state;
    //             $cropinsertData['state'] = $state;
    //             $cropinsertData['state_price'] = $state_price;

    //             $insert_crop_prices=CropPrice::create($cropinsertData);

    //             $id= $insert_crop_prices->id;

    //             $crop_price_data = CropPrice::find($id);

    //             return response()->json([
    //                 'msg' => 'Crop data stored successfully',
    //                 'data' => $crop_price_data,
    //                 'statuscode'=>'200',
    //                 'status' =>'success'
    //             ]);

    //         }
    //     }


    // }

    public function update_crop_prices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cropData.crop_name' => 'string',
            'cropData.crop_id' => 'string',
            'cropData.base_price' => 'numeric',
            'cropData.availability.*.state' => 'string',
            'cropData.availability.*.state_price' => 'numeric',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors(), 'statuscode' => '400', 'status' => 'error']);
        }

        $data = $request->all();
        $cropData = $data['cropData'];
        // return $cropData;

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // Create or update availability records
            $insertedRecords = [];
            foreach ($cropData['availability'] as $availabilityData) {

                // return $availabilityData['id'];

                // return $availabilityData['id'];
               

                $existingAvailability = CropPrice::where('id', $availabilityData['id'])
                    ->first();

                // return $existingAvailability;
                if (empty($existingAvailability) && !empty($availabilityData['id'])) {
                    // Rollback the transaction if record already exists
                    DB::rollBack();

                    return response()->json([
                        'msg' => 'Record Does not exists',
                        'data' => $availabilityData['id'],
                        'statuscode' => '400',
                        'status' => 'error'
                    ]);
                } else {
                    $details = Auth::user();
                    if (isset($cropData['base_price'])) {
                        $crop_base_price_insert['base_price'] = $cropData['base_price'];
                        $crop_base_price = Crop::where('id', $cropData['crop_id'])->update(['base_price' => $crop_base_price_insert['base_price']]);
                    }
                    if (isset($availabilityData['state_price'])) {
                        $cropinsertData['state_price'] = $availabilityData['state_price'];
                    }
                    $cropinsertData['updated_by_name'] = $details->name;
                    $cropinsertData['updated_by_id'] = $details->id;

                    // print_r($availabilityData['id']);
                


                    if (empty($availabilityData['id'])) {
                        $crop_new_data['state'] = $availabilityData['state'];
                        $crop_new_data['state_price'] = $availabilityData['state_price'];
                        $crop_new_data['saved_by_name'] = $details->name;
                        $crop_new_data['saved_by_id'] = $details->id;
                        $crop_new_data['crop_id'] = $cropData['crop_id'];

                        $get_crop_name=Crop::where('id', $crop_new_data['crop_id'])->first();
                        $crop_new_data['crop_name']= $get_crop_name->crop_name;

                        $insert_new_crop=CropPrice::create($crop_new_data);

                    }else{
                        $updatedcrop = CropPrice::find($availabilityData['id']);
                        $insertedRecord =  $updatedcrop->update($cropinsertData);

                    }


                    $insertedRecords[] = $insertedRecord;
                }
            }

            // Commit the transaction after processing all records
            DB::commit();

            return response()->json([
                'msg' => 'Crop data Updated successfully',
                'data' => $insertedRecords,
                'statuscode' => '200',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            // Something went wrong, rollback the transaction
            DB::rollBack();

            return response()->json([
                'msg' => 'Error Updating crop data',
                'statuscode' => '500',
                'status' => 'error',
                'data' => $e->getMessage()
            ]);
        }
    }
}
