<?php

namespace App\Http\Controllers;

use App\Models\OperatorReimbursementDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


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

       $check_users_table=User::where('id', $validatedData['user_id'])->first();

       if(empty($check_users_table))
       {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Failed to submit reimbursement for non existence user', 'data' => []], 200);

       }

       if(!$check_users_table->status)
       {
            return response()->json(['status' => 'error', 'statuscode' => '200', 'msg' => 'Failed to submit reimbursement for inactive user','data'=>[]], 200);

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
            'attachment' => 'image|mimes:jpeg,png,jpg,gif,pdf',
            'user_id' => 'string', // Assuming user_id is a string
        ]);
        $data = $request->all();
        $id=$data['id'];

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


        if($reimbursement->status !=1)
        {
            return response()->json([
                'status' => 'error',
                'statuscode' => '404',
                'msg' => 'Status of this Reimbursement is not in Created Mode',
            ], 200);
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
            ->whereDate('to_date', '<=', $toDate);

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
        $query = OperatorReimbursementDetail::select('category', 'claimed_amount', 'id')
            ->where('user_id', $userId)
            ->whereDate('from_date', '>=', $fromDate)
            ->whereDate('to_date', '<=', $toDate);

        // If category is provided, add it to the query
        if ($category) {
            $query->where('category', $category);
        }

        // Execute the query
        $dashboardData = $query->get();

        // Format the response
        $formattedData = [];
        foreach ($dashboardData as $data) {
            $formattedData[] = [
                'id' => $data->id,
                'category' => $data->category,
                'amount' => $data->claimed_amount,
            ];
        }

        return response()->json([
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Data fetched successfully.',
            'data' => $formattedData
        ], 200);
    }
}
