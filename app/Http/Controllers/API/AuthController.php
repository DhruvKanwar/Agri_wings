<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if (preg_match("/([%\$#{}!()+\=\-\*\'\"\/\\\]+)/", request('email'))) {
            $result_array = array(
                'status' => 'fail',
                'statuscode' => '405',
                'msg' => 'Invalid characters given'
            );

            return response()->json($result_array, 200);
        }

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->first('email')) {
                return response()->json(['status' => 'error',  'statuscode' => '400', 'msg' => $errors->first('email')], 400);
            }
            if ($errors->first('password')) {
                return response()->json(['status' => 'error',  'statuscode' => '400', 'msg' => $errors->first('password')], 400);
            }

            return response()->json(['error' => $validator->errors()], 400);
        }

        $data=$request->all();
        $check_inactive_user=User::where('email',$data['email'])->first();
        if (empty($check_inactive_user)) {
            return response()->json(['msg' => 'User Not Exists.', 'statuscode' => '403', 'data' => [], 'status' => 'error'], 400);
        }

        if($check_inactive_user->status == 0)
        {
            return response()->json(['msg' => 'User is Inactive','statuscode'=>'403','data'=>[],'status'=>'error'], 400);

        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;
            $user['role']=  $user->roles()->first();
            $send_api_res['user'] = $user;
            $send_api_res['statuscode'] = '200';
            $send_api_res['msg'] = 'Login Successful';
            $send_api_res['accessToken'] = $token;
            return $send_api_res;
        }else{
            $result_array = array(
                'status' => 'fail',
                'statuscode'=>'403',
                'msg' => 'Invalid credentials entered'
            );
            return response()->json($result_array, 200);
        }

      
    }


    public function test(Request $request)
    {
        $data = $request->all();
        return $data;
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
            return response()->json(['msg' => 'Successfully logged out', 'statuscode' => '200'],200);
        }

        return response()->json(['msg' => 'Token not found', 'statuscode' => '401'], 401);
    }

    public function operator_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login_id' => 'required',
            'password' => 'required',
        ]);

        if (preg_match("/([%\$#{}!()+\=\-\*\'\"\/\\\]+)/", request('email'))) {
            $result_array = array(
                'status' => 'fail',
                'statuscode' => '405',
                'msg' => 'Invalid characters given'
            );

            return response()->json($result_array, 200);
        }

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->first('login_id')) {
                return response()->json(['status' => 'error',  'statuscode' => '400', 'msg' => $errors->first('email')], 400);
            }
            if ($errors->first('password')) {
                return response()->json(['status' => 'error',  'statuscode' => '400', 'msg' => $errors->first('password')], 400);
            }

            return response()->json(['error' => $validator->errors()], 400);
        }

        $credentials = $request->only('login_id', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;
            $user['role'] =  $user->roles()->first();
            $send_api_res['user'] = $user;
            $send_api_res['statuscode'] = '200';
            $send_api_res['msg'] = 'Login Successful';
            $send_api_res['accessToken'] = $token;
            return $send_api_res;
        } else {
            $result_array = array(
                'status' => 'fail',
                'statuscode' => '403',
                'msg' => 'Invalid credentials entered'
            );
            return response()->json($result_array, 200);
        }

    }
}
