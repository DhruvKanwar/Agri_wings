<?php

namespace App\Http\Controllers;

use App\Models\AttendanceOperator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
date_default_timezone_set('Asia/Kolkata');
ini_set('max_execution_time', -1);

class AttendanceController extends Controller
{
    //
    public function clockIn(Request $request)
    {
       
    
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'user_name' => 'required|string',
            'user_mobile_no' => 'required|string',
            'date' => 'required|date',
            'in' => 'required|date_format:H:i:s',
        ]);

        $data=$request->all();
     

        if ($validator->fails()) {
            return response()->json([
                'msg' => 'The given data was invalid.',
                'data' => $validator->errors(),
                'statuscode'=>'400'
            ], 422);
        }
        $id = $data['user_id'];
        $check_order_exists = User::where('id', $id)->first();
        // return $check_order_exists;
        if (empty($check_order_exists)) {
            return response()->json(['msg' => 'User Does not exists', 'status' => 'success', 'statuscode' => '200', 'data' => []], 201);
        }

        // Create a new attendance operator instance
        $attendanceOperator = new AttendanceOperator([
            'user_id' => $request->user_id,
            'user_name' => $request->user_name,
            'user_mobile_no' => $request->user_mobile_no,
            'date' => $request->date,
            'in' => $request->in,
        ]);

        // Save the attendance operator instance
        $attendanceOperator->save();

        // Optionally, you can return a response indicating success
        return response()->json(['msg' => 'Clock in details added successfully','status'=>'success','statuscode'=>'200'], 201);
    }


    public function clockOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'out' => 'required|date_format:H:i:s',
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

        // Update the clock out time
        $attendanceOperator->out = $request->out;

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

}
