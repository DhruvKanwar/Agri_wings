<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $data = User::with('roles')->orderBy('id', 'DESC')->paginate(5);



        // Transform the data to include roles
        $formattedData = $data->map(function ($user) {
            // Get all roles from the database
            $allRoles = Role::pluck('name')->toArray();
            $users = array();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
                'all_roles' => $allRoles,
                // Include any other user details you want in the response
            ];

            // return [
            //     $users['id'] => $user->id,
            //     $users['name'] => $user->name,
            //     $users['email'] => $user->email,
            //     $users['roles'] => $user->getRoleNames()->toArray(),
            //     $users['all_roles'] => $allRoles,
            //     // Include any other user details you want in the response
            // ];
        });

        if (!empty($data)) {
            return response()->json(['users' => $formattedData, 'statuscode' => '200', 'msg' => 'User Fetched Sucessfully..']);
        } else {
            return response()->json(['statuscode' => '200', 'msg' => 'User Table NOt Found']);
        }
    }

    // ... other methods

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'roles' => 'required|array'
        ]);

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        return response()->json(['message' => 'User created successfully']);
    }

    // ... other methods

    public function update(Request $request)
    {
        $input = $request->all();
        $id = $input['id'];
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'sometimes|confirmed',
            'role' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::find($id);

        // Get roles assigned to the user
        $roles = $user->getRoleNames();
        // dd($request->input('role'));
        //   start permission
        if ($roles != "super-admin" && $input['role'] == 'super-admin') {
            $user = User::find($id); // Replace 1 with the user ID you want to assign permissions to

            // Assign multiple permissions to the user
            $user->givePermissionTo([
                'permission-list',
                'permission-create',
                'permission-edit',
                'permission-delete',
            ]);
        }
        // end permission

        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, ['password']);
        }

        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id', $id)->delete();

        $user->assignRole($request->input('role'));

        return response()->json(['message' => 'User updated successfully']);
    }
}
