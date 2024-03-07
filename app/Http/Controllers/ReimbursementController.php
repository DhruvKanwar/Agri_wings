<?php

namespace App\Http\Controllers;

use App\Exports\ExportTerList;
use App\Models\AssetOperator;
use App\Models\OperatorReimbursementDetail;
use App\Models\Ter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Stmt\Return_;

class ReimbursementController extends Controller
{
    //

    public function submit_operator_reimbursement(Request $request)
    {
        // Define custom validation messages
        $messages = [
            'to_date.after_or_equal' => 'The to date must be after or equal to the from date.',
            'claimed_amount.lte' => 'The claimed amount must be less than or equal to the bill amount.'
        ];

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'category' => 'required|string',
            'bill_no' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'bill_amount' => 'required|numeric',
            'claimed_amount' => 'required|numeric|lte:bill_amount',
            'remarks' => 'required|string',
            'attachment' => 'required|image|mimes:jpeg,png,jpg,gif,pdf',
            'user_id' => 'required|string', // Assuming user_id is a string
        ], $messages);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 200);
        }

        // Validate the request
        $validatedData = $validator->validated();

        $check_users_table = User::where('id', $validatedData['user_id'])->first();


        if (empty($check_users_table)) {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Failed to submit reimbursement for non existence user', 'data' => []], 200);
        }

        if (!$check_users_table->status) {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Failed to submit reimbursement for inactive user', 'data' => []], 200);
        }


        $check_ter_table = Ter::where('user_id', $validatedData['user_id'])
            ->whereYear('from_date', '=', date('Y', strtotime($validatedData['from_date'])))
            ->whereMonth('from_date', '=', date('m', strtotime($validatedData['from_date'])))
            ->whereYear('to_date', '=', date('Y', strtotime($validatedData['to_date'])))
            ->whereMonth('to_date', '=', date('m', strtotime($validatedData['to_date'])))
            ->where('status', '!=', 3)
            ->get();

        if (count($check_ter_table) != 0) {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Ter already submitted for this month', 'data' => []], 200);
        }


        $attachment = $request->file('attachment');
        if (!empty($attachment)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'Reimburse_' . $randomString . '.' . $attachment->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $attachment->storeAs('reimburse', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $validatedData['attachment'] = $customFilename;
        }

        // Create a new OperatorReimbursementDetail instance
        $reimbursement = OperatorReimbursementDetail::create($validatedData);

        // Return response
        if ($reimbursement) {
            return response()->json(['status' => 'success', 'statuscode' => '200', 'msg' => 'Reimbursement submitted successfully'], 200);
        } else {
            return response()->json(['status' => 'error', 'statuscode' => '500', 'msg' => 'Failed to submit reimbursement'], 500);
        }
    }



    public function edit_operator_reimbursement(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'category' => 'string',
            'bill_no' => 'string',
            'from_date' => 'date',
            'to_date' => 'date|after_or_equal:from_date',
            'bill_amount' => 'numeric',
            'claimed_amount' => 'numeric|lte:bill_amount',
            'remarks' => 'string',
            'user_id' => 'string', // Assuming user_id is a string
        ]);
        $data = $request->all();
        $id = $data['id'];

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 200);
        }


        // Find the existing reimbursement
        $reimbursement = OperatorReimbursementDetail::find($id);


        if ($reimbursement->status != 1) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '404',
                'msg' => 'Status of this Reimbursement is not in Created Mode',
            ], 200);
        } else if ($reimbursement->status == 0) {
            $reimbursement->update(['status' => 0]);

            // Return response
            return response()->json(['status' => 'success', 'statuscode' => '200', 'msg' => 'Reimbursement updated successfully'], 200);
        }

        // Check if the reimbursement exists
        if (!$reimbursement) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '404',
                'msg' => 'Reimbursement not found.',
            ], 200);
        }
        $attachment = $request->file('attachment');
        if (!empty($attachment)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'Reimburse_' . $randomString . '.' . $attachment->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $attachment->storeAs('reimburse', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $data['attachment'] = $customFilename;
        }

        $reimbursement->update($data);

        // Return response
        return response()->json(['status' => 'success', 'statuscode' => '200', 'msg' => 'Reimbursement updated successfully'], 200);
    }


    public function get_all_reimbursements(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'Invalid input data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->input('user_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $category = $request->input('category');

        // Start building the query
        $query = OperatorReimbursementDetail::where('user_id', $userId)
            ->whereDate('from_date', '>=', $fromDate)
            ->whereDate('to_date', '<=', $toDate)
            ->where('status', '!=', 0);

        // If category is provided, add it to the query
        if ($category) {
            $query->where('category', $category);
        }

        // Execute the query
        $dashboardData = $query->get();



        return response()->json([
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Data fetched successfully.',
            'data' => $dashboardData
        ], 200);
    }

    public function get_reimburse_dashboard_details(Request $request)
    {

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'Invalid input data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->input('user_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $category = $request->input('category');

        // Start building the query
        $query = OperatorReimbursementDetail::select('category', 'claimed_amount', 'id', 'status')
            ->where('user_id', $userId)
            ->whereDate('from_date', '>=', $fromDate)
            ->whereDate('to_date', '<=', $toDate)
            ->where('status', 2)->get();

        if (count($query) == 0) {
            $query = OperatorReimbursementDetail::select('category', 'claimed_amount', 'id', 'status')
                ->where('user_id', $userId)
                ->whereDate('from_date', '>=', $fromDate)
                ->whereDate('to_date', '<=', $toDate)
                ->where('status', 1)->get();
        }
        // // If category is provided, add it to the query
        // if ($category) {
        //     $query->where('category', $category);
        // }

        // Execute the query
        $dashboardData = $query;

        $groupedData = $dashboardData->groupBy('category');
        // Format the response
        $formattedData = [];
        foreach ($groupedData  as $category => $items) {
            $totalAmount = $items->sum('claimed_amount');
            $formattedData[] = [
                // 'id' => $data->id,
                'category' => $category,
                'amount' => $totalAmount,
                'status' => $items[0]->status
            ];
        }

        return response()->json([
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Data fetched successfully.',
            'data' => $formattedData
        ], 200);
    }


    public function final_ter_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'da_amount' => 'required|string',
            'total_attendance' => 'required|string',
            'total_claimed_amount' => 'required|string',
            'da_limit' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'Invalid input data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        $userId = $data['user_id'];
        $fromDate = $data['from_date'];
        $toDate = $data['to_date'];

        $check_ter_exist = Ter::where('user_id', $userId)->whereDate('from_date', '>=', $fromDate)
            ->whereDate('to_date', '<=', $toDate)
            ->whereIn('status', [1, 2])->get();
        if (count($check_ter_exist) != 0) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Ter Already Exists..',
                'data' => []
            ], 200);
        }
        $get_current_reimburse_details = OperatorReimbursementDetail::where('user_id', $userId)
            ->whereDate('from_date', '>=', $fromDate)
            ->whereDate('to_date', '<=', $toDate)
            ->where('status', 1)->get();

        $categoryIds = [];
        $totalCategoryAmount = 0;

        foreach ($get_current_reimburse_details as $detail) {
            $categoryIds[] = $detail->id;
            $totalCategoryAmount += $detail->claimed_amount;
        }

        $check_claimed_amount = $totalCategoryAmount + $data['da_amount'];
        $data['total_category_amount'] = $totalCategoryAmount;
        $data['submit_date'] = date('Y-m-d');

        $check_da_amount = $data['da_limit'] * $data['total_attendance'];

        if ($check_da_amount != $data['da_amount']) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Da Amount not matching',
                'data' => []
            ], 200);
        }

        if ($check_claimed_amount != $data['total_claimed_amount']) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Claimed Amount not matching',
                'data' => []
            ], 200);
        }

        $user_details=User::where('id',$userId)->first();

        $assetOperator_table=AssetOperator::where('user_id', $user_details->login_id)->first();
        $data['operator_id']=$assetOperator_table->id;

        DB::beginTransaction();

        try {
            // Add category_ids to the data array
            $data['category_ids'] = implode(',', $categoryIds);

            // Create Ter record
            $ter = Ter::create($data);

            if ($ter) {
                // Update OperatorReimbursementDetail records
                OperatorReimbursementDetail::whereIn('id', $categoryIds)
                    ->update(['status' => 2, 'unid' => $ter->id]);
            }

            DB::commit();

            // Return success response
            return response()->json(['msg' => 'TER details submitted successfully', 'statuscode' => '200', 'status' => 'success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            // Handle the exception
            return response()->json([
                'status' => 'error',
                'statuscode' => '500',
                'msg' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_ter_list(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'statuscode' => '422',
                'msg' => 'Invalid input data.',
                'errors' => $validator->errors(),
            ], 200);
        }


        $data = $request->all();

        $fromDate = $data['from_date'];
        $toDate = $data['to_date'];

        $user_id = $data['user_id'];

        if (!empty($user_id)) {
            $data = Ter::with('operatorReimbursement','assetOperator')
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->where('from_date', '>=', $fromDate)
                        ->where('from_date', '<=', $toDate);
                })
                ->orWhere(function ($query) use ($fromDate, $toDate) {
                    $query->where('to_date', '>=', $fromDate)
                        ->where('to_date', '<=', $toDate);
                })->where('user_id', $user_id)
                ->get();
        } else {
            $data = Ter::with('operatorReimbursement', 'assetOperator')
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->where('from_date', '>=', $fromDate)
                        ->where('from_date', '<=', $toDate);
                })
                ->orWhere(function ($query) use ($fromDate, $toDate) {
                    $query->where('to_date', '>=', $fromDate)
                        ->where('to_date', '<=', $toDate);
                })
                ->get();
        }



        return response()->json(['status' => 'success', 'statuscode' => '200', 'data' => $data, 'msg' => 'Ter List Fetched Successfully...']);
    }

    public function update_ter_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'statuscode' => '422',
                    'msg' => 'Invalid input data.',
                    'errors' => $validator->errors(),
                ],
                422
            );
        }

        $data = $request->all();

        $id = $data['id'];
        $check_ter_table = Ter::where('id', $id)->first();
        if (empty($check_ter_table)) {
            return response()->json(
                [
                    'status' => 'error',
                    'statuscode' => '200',
                    'msg' => 'Ter Does not exists.',
                    'data' => []
                ],
                200
            );
        }

        if ($data['status'] == 2) {

            $update_ter = Ter::where('id', $id)->update(['status' => 2, 'hr_updated_date' => date('Y-m-d')]);

            if ($update_ter) {
                OperatorReimbursementDetail::where('unid', $id)->update(['status' => 3]);
            }
        } else if ($data['status'] == 3) {

            $update_ter = Ter::where('id', $id)->update(['status' => 3, 'hr_updated_date' => date('Y-m-d'), 'remarks' => $data['remarks']]);

            if ($update_ter) {
                OperatorReimbursementDetail::where('unid', $id)->update(['status' => 4]);
            }
        }


        return response()->json(
            [
                'status' => 'success',
                'statuscode' => '200',
                'msg' => 'Ter Updated Successfully..'
            ],
            200
        );
    }

    public function download_ter_list(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        // Ensure that both from_date and to_date are present in the request
        if (!$fromDate || !$toDate) {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Input Missing']);

        }

        // Validate that from_date is not less than to_date
        if ($fromDate > $toDate) {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Invalid date range: from_date cannot be greater than to_date.']);
         
        }

        $export = new ExportTerList($fromDate, $toDate);

        // Generate the export data
        $exportData = $export->collection();


        return $exportData;
     
        return Excel::download(new ExportTerList($fromDate,$toDate), 'Ter_List_.'.date('d-m-Y').'.xlsx');
    }
}

    // public function final_ter_submit(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //         'from_date' => 'required|date',
    //         'to_date' => 'required|date|after_or_equal:from_date',
    //         'da_amount' => 'required|string',
    //         'total_attendance' => 'required|string',
    //         'total_claimed_amount' => 'required|string',
    //         'da_limit' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'statuscode' => '422',
    //             'msg' => 'Invalid input data.',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     $data=$request->all();

    //     $userId=$data['user_id'];
    //     $fromDate=$data['from_date'];
    //     $toDate = $data['to_date'];


    //     $get_current_reimburse_details = OperatorReimbursementDetail::where('user_id', $userId)
    //         ->whereDate('from_date', '>=', $fromDate)
    //         ->whereDate('to_date', '<=', $toDate)
    //         ->where('status', 1)->get();

    //     $categoryIds = [];
    //     $totalCategoryAmount = 0;

    //     foreach ($get_current_reimburse_details as $detail) {
    //         $categoryIds[] = $detail->id;
    //         $totalCategoryAmount += $detail->claimed_amount;
    //     }

    //     $check_claimed_amount=$totalCategoryAmount+$data['da_amount'];
    //     $data['total_category_amount']= $totalCategoryAmount;
    //     $data['submit_date'] = date('Y-m-d');



    //     $check_da_amount =  $data['da_limit'] * $data['total_attendance'];

    //     if ($check_da_amount != $data['da_amount']) {

    //         return response()->json([
    //             'status' => 'error',
    //             'statuscode' => '200',
    //             'msg' => 'Da Amount not matching',
    //             'data' => []
    //         ], 200);
    //     }

    //     if($check_claimed_amount != $data['total_claimed_amount'])
    //     {

    //         return response()->json([
    //             'status' => 'error',
    //             'statuscode' => '200',
    //             'msg' => 'Claimed Amount not matching',
    //             'data' => []
    //         ], 200);
    //     }


    //     // Convert categoryIds array to a comma-separated string
    //     $data['category_ids'] = implode(',', $categoryIds);



    //     $ter = Ter::create($data);

    //     if ($ter) {
    //         $explode_category_ids = explode(',', $data['category_ids']);

    //         // Update OperatorReimbursementDetail records in a single query
    //         OperatorReimbursementDetail::whereIn('id', $explode_category_ids)
    //         ->update(['status' => 2, 'unid' => $ter->id]);
    //     }
    

    //     // Return success response
    //     return response()->json(['message' => 'TER details submitted successfully'], 200);
    // }
