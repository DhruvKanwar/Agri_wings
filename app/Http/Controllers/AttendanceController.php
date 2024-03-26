<?php

namespace App\Http\Controllers;

use App\Models\AssetOperator;
use App\Models\AttendanceOperator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


date_default_timezone_set('Asia/Kolkata');
ini_set('max_execution_time', -1);

class AttendanceController extends Controller
{
    //
    public function clockIn(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'in' => 'required|date_format:H:i:s',
        ]);

        $data = $request->all();


        if ($validator->fails()) {
            return response()->json([
                'msg' => 'The given data was invalid.',
                'data' => $validator->errors(),
                'statuscode' => '400'
            ], 422);
        }
        $id = $data['user_id'];
        $check_user_exists = User::where('id', $id)->first();
        // return $check_user_exists;
        if (empty($check_user_exists)) {
            return response()->json(['msg' => 'User Does not exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        }


        $get_asset_operator = AssetOperator::where('user_id', $check_user_exists->login_id)->first();
        // return [ $check_user_exists->login_id,$get_asset_operator];
        // Create a new attendance operator instance    
        $check_attendance_table = AttendanceOperator::where('user_id', $request->user_id)
            ->where('date', $request->date)
            ->get();
        // return $check_attendance_table;
        if (!$check_attendance_table->isEmpty()) {
            return response()->json(['msg' => 'Clock in Entry Already Exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        }
        $attendanceOperator = new AttendanceOperator([
            'user_id' => $request->user_id,
            'user_name' => $check_user_exists->name,
            'user_mobile_no' => $get_asset_operator->phone,
            'date' => $request->date,
            'in' => $request->in,
        ]);

        // Save the attendance operator instance
        $attendanceOperator->save();

        // Optionally, you can return a response indicating success
        return response()->json(['msg' => 'Clock in details added successfully', 'status' => 'success', 'statuscode' => '200'], 201);
    }


    public function clockOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'out' => 'required|date_format:H:i:s',
            'remarks'=> 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => 'The given data was invalid.',
                'data' => $validator->errors(),
                'statuscode' => '400'
            ], 422);
        }

        // Find the attendance record for the user and date
        $attendanceOperator = AttendanceOperator::where('user_id', $request->user_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        if (!$attendanceOperator) {
            return response()->json([
                'msg' => 'No clock in record found for the user today.',
                'statuscode' => '404'
            ], 404);
        }

        $check_attendance_table = AttendanceOperator::where('user_id', $request->user_id)
            ->where('date', date('Y-m-d'))
            ->first();
        // return $check_attendance_table;
        if (!empty($check_attendance_table->out)) {
            return response()->json(['msg' => 'Clock Out Entry Already Exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        }

        // Update the clock out time
        $attendanceOperator->out = $request->out;

        $attendanceOperator->remarks=$request->remarks;

        // Calculate the working hours
        $startTime = Carbon::parse($attendanceOperator->in);
        $endTime = Carbon::parse($request->out);
        $diffInMinutes = $endTime->diffInMinutes($startTime);
        $hours = floor($diffInMinutes / 60); // Extract the whole number of hours
        $minutes = $diffInMinutes % 60; // Extract the remaining minutes
        $workingHours = $hours + ($minutes / 100); // Calculate working hours with decimal for minutes

        // Update the working hours field
        $attendanceOperator->working_hours = $workingHours;
        $attendanceOperator->save();

        return response()->json([
            'msg' => 'Clock out details added successfully',
            'status' => 'success',
            'statuscode' => '200'
        ], 200);
    }

    public function get_user_attendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'from' => 'required|string',
            'to' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => 'The given data was invalid.',
                'data' => $validator->errors(),
                'statuscode' => '400'
            ], 200);
        }
        $data=$request->all();
        $user_id=$data['user_id'];
        $fromDate=$data['from'];
        $toDate = $data['to'];

        if(!empty($user_id))
        {
            $attendanceOperator = AttendanceOperator::where('user_id', $user_id)
                ->whereBetween('date', [$fromDate, $toDate])
            ->get(); 
        }else{
            $attendanceOperator = AttendanceOperator::whereBetween('date', [$fromDate, $toDate])
                ->get(); 
        }


        if (empty($attendanceOperator)) {
            return response()->json([
                'msg' => 'No records found.',
                'statuscode' => '200'
            ]);
        }

        return response()->json([
            'msg' => 'Data fetched Successfully...',
            'status' => 'success',
            'statuscode' => '200',
            'data'=>$attendanceOperator
        ], 200);
    }


    public function autoClockOut()
    {
        // Get the current date
        $currentDate = Carbon::today()->toDateString();

        // Find all attendance records with clock in for the current date
        $attendanceOperators = AttendanceOperator::whereDate('date', $currentDate)
            ->whereNull('out')
            ->get();

        foreach ($attendanceOperators as $attendanceOperator) {
            // Set clock out time to current time
            $endTime = Carbon::now();

            // Calculate working hours
            $startTime = Carbon::parse($attendanceOperator->in);
            $diffInMinutes = $endTime->diffInMinutes($startTime);

            // Calculate hours and minutes separately
            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;

            // Concatenate hours and minutes with a decimal point
            $workingHours = $hours + ($minutes / 100);

            // Update working hours
            $attendanceOperator->working_hours = $workingHours;

            // Set clock out time to current time
            $attendanceOperator->out = $endTime->toTimeString();

            // Save the changes
            $attendanceOperator->save();
        }

        return response()->json(['msg' => 'Automatic clock out completed successfully'], 200);
    }
    public function fetch_operator_attendance()
    {
        $details = Auth::user();
        $id = $details->id;

        $fetch_data = AttendanceOperator::where('user_id', $id)->where('date', date('Y-m-d'))->get();
        if (empty($fetch_data)) {
            return response()->json([
                'msg' => 'Attendance Details Not Found.',
                'status' => 'success',
                'statuscode' => '200',
                'data' => []
            ], 200);
        } else {
            return response()->json([
                'msg' => 'Attendance Details Fetched Successfully..',
                'status' => 'success',
                'statuscode' => '200',
                'data' => $fetch_data
            ], 200);
        }
    }
}
