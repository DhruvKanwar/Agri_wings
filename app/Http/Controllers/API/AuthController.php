<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

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
                'msg' => 'Invalid characters given'
            );

            return response()->json($result_array, 405);
        }

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->first('email')) {
                return response()->json(['status' => 'error', 'msg' => $errors->first('email')], 400);
            }
            if ($errors->first('password')) {
                return response()->json(['status' => 'error', 'msg' => $errors->first('password')], 400);
            }

            return response()->json(['error' => $validator->errors()], 400);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;
            $role=  $user->roles()->first();;
            $send_api_res['role'] = $role;
            $send_api_res['user'] = $user;
            $send_api_res['accessToken'] = $token;
            return $send_api_res;
        }else{
            $result_array = array(
                'status' => 'fail',
                'msg' => 'Invalid credentials entered'
            );
            return response()->json($result_array, 403);
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
            return response()->json(['message' => 'Successfully logged out']);
        }

        return response()->json(['message' => 'Token not found'], 404);
    }
}
