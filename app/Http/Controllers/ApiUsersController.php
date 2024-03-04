<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiUsersController extends Controller
{
    //

    public function get_all_users()
    {
        $users = User::where('role','!=', 'operator')->get();
        // $users['all_roles'] = Role::pluck('name')->toArray();

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Users List Fetched Successfully',
            'data' => $users
        );

        return response()->json($result_array, 200);
    }

    public function roles_list()
    {

        $roles = Role::pluck('name')->toArray();

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'Roles List Fetched Successfully',
            'data' => $roles
        );

        return response()->json($result_array, 200);
    }

    public function create_user(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'role' => 'nullable|string', // Add validation for role
        ]);

        if ($validator->fails()) {
            // return response()->json(['error' => $validator->errors(),], 422);
            return response()->json(['status' => 'error', 'statuscode' => '400', 'msg' => $validator->errors(),'data'=>[]]);
        }

        // Create new user
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->text_password = $request->input('password');


        if (!empty($request->input('role'))) {
            $user->role = $request->input('role');
        }
        if (!empty($request->input('client_id'))) {
            $user->client_id = $request->input('client_id');
        }
        $user->save();

        // Assign role to the user
        if (!empty($request->input('role'))) {
            $role = Role::where('name', $request->input('role'))->first(); // Retrieve the role by its name
            $user->roles()->attach($role);
        }

        if (!empty($request->input('client_id'))) {
            $user->client_id = $request->input('client_id');
        }


        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'User Created Successfully',
            'data' => $user
        );

        return response()->json($result_array, 200);
    }

    public function edit_user(Request $request)
    {
        // Find the user
        $data = $request->all();
        $id = $data['id'];
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error', 'statuscode' => '404', 'msg' => 'User not found'], 404);
        }

        if ($user->text_password != $data['password']) {
            $data['text_password'] = $data['password'];
            $data['password'] = Hash::make($data['text_password']);
        } else {
            unset($data['password']);
        }

        if (
            $user->role != $data['role']
        ) {
            // return   $data['role'] ;
            $get_role = Role::where('name', $user->role)->first();

            if (!empty($get_role)) {
                $user->roles()->detach($get_role->id);
            }

            // Revoke user's API token if exists

            $tokens = DB::table('personal_access_tokens')->where('tokenable_id', $user->id)
            ->get();
           
            if(!empty($tokens))
            {
                foreach ($tokens as $token) {
                    DB::table('personal_access_tokens')->where('id', $token->id)->delete();
                }
        
            }
         
          
            // $user->roles()->attach($get_role->id);
        } else {
            unset($data['role']);
        }

  
        if (
            $user->client_id == $data['client_id']
        ) {
            unset($data['client_id']);
        }

        User::where('id',$id)->update($data);


        $user=User::find($id);

        $result_array = array(
            'status' => 'success',
            'statuscode' => '200',
            'msg' => 'User Updated Successfully',
            'data' => $user
        );

        return response()->json($result_array, 200);
    }
}
