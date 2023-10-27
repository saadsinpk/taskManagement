<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Routes;
use App\Models\RoleHasPermission;
use App\Models\GeneralSetting;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public $successStatus = 200;
    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */

    //Login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Authentication successful
            $user = Auth::user();
            if ($user->remember_token != null) {
                $token = $user->remember_token;
            } else {
                $token = $user->createToken('authToken')->accessToken;
            }
            $user->update(['remember_token' => $token]);
            $data = array('user' => $user, 'token' => $token);

            return response()->json(['data' => $data], 200);
        }

        // Authentication failed
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    //AuthUserData
    public function authUserData()
    {
        $id = Auth::user()->id;
        $user = User::with('role')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }
        $permiss = RoleHasPermission::where('role_id', $user->role)->get();
        $routes = Routes::all();
        $permissions = [];

        if ($id == 1) {
            $permissions = [
                'superadmin' => true,
            ];
        } else {
            $allTitles = array_merge($permiss->pluck('title')->toArray(), $routes->pluck('title')->toArray());

            $allTitles = array_unique($allTitles);

            foreach ($allTitles as $title) {
                $permissions[$title] = false;
            }

            foreach ($permiss as $permission) {
                $inRoutes = Routes::where('title', $permission->title)->exists();
                if ($inRoutes) {
                    $permissions[$permission->title] = true;
                }
            }
        }
        $generalsetting = GeneralSetting::where('user_id', $id)->get();

        $notificationCount = Notification::where('user_id', $user->id)->where('read_at', 0)->count();

        return response()->json(['user' => $user, 'RolesAndPermissions' => $permissions, 'generalSetting' => $generalsetting, 'routes' => $routes, 'notification' => $notificationCount], 200);
    }

    //User Management
    public function userCreate(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'fname' => 'required',
            'lname' => 'required',
            'role' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        // if (isset($input['profile'])) {
        //     //     $uploadedFile = $request->file('profile');
        //     //     $originalFilename = 'profile';

        //     //     // Get the file extension
        //     //     $extension = $uploadedFile->getClientOriginalExtension();

        //     //     // Generate a unique filename based on the original filename, current date/time, and extension
        //     //     $currentDateTime = now()->format('Ymd-His');
        //     //     $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
        //     //     $input['profile'] = $uploadedFile->storeAs('userProfile', $filename, 'public');
        // }
        $input['password'] = bcrypt($input['password']);
        $input['created_by'] = $user->id;
        $user = User::create($input);
        if ($user) {
            return response()->json(['message' => 'User created successfully'], 201);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function userList()
    {
        // return 'hy';
        $users = User::all(); // Include the 'role' relationship
        foreach ($users as $user) {
            if ($user->role) {
                $data = Role::select('name')->where('id', $user->role)->first();
                if ($data) {
                    $user['roleName'] = $data->name;
                } else {
                    // Handle the case where the role with the given ID does not exist.
                    $user['roleName'] = '';
                }
            } else {
                $user['roleName'] = 'superadmin';
            }
        }

        return response()->json(['users' => $users], 200);
    }

    public function userEdit($id)
    {
        $user = User::with('role')->where('id', $id)->first(); // Use first() to retrieve a single record
        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }
        $roles = Role::all();
        return response()->json(['user' => $user, 'roles' => $roles], 200);
    }

    public function userUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id', // Validate that the 'id' exists in the 'users' table
        ]);

        $user = User::find($request->id); // Use 'find()' to retrieve a single record by primary key

        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }
        $input = $request->all();
        // Check if the 'password' key exists in the input data
        if (isset($input['password'])) {
            // Hash the password if it exists in the input data
            $input['password'] = bcrypt($input['password']);
        }
        if (isset($input['profile'])) {
            $uploadedFile = $request->file('profile');
            $originalFilename = 'profile';

            // Get the file extension
            $extension = $uploadedFile->getClientOriginalExtension();

            // Generate a unique filename based on the original filename, current date/time, and extension
            $currentDateTime = now()->format('Ymd-His');
            $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
            $input['profile'] = $uploadedFile->storeAs('userProfile', $filename, 'public');
        }
        $user->update($input);

        return response()->json(['message' => 'user updated successfully'], 200);
    }

    public function userDelete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    //Profile
    public function userProfile()
    {
        $user = Auth::user();

        $id = $user->id;

        $user = User::with('role')->where('id', $id)->first(); // Use first() to retrieve a single record
        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }
        return response()->json(['user' => $user], 200);
    }

    public function userProfileUpdate(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:users,id', // Validate that the 'id' exists in the 'users' table
        ]);

        $user = User::find($request->id); // Use 'find()' to retrieve a single record by primary key

        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }

        $input = $request->all();
        // Check if the 'password' key exists in the input data
        if (isset($input['password'])) {
            // Hash the password if it exists in the input data
            $input['password'] = bcrypt($input['password']);
        }
        if (isset($input['profile'])) {
            $uploadedFile = $request->file('profile');
            $originalFilename = 'profile';

            // Get the file extension
            $extension = $uploadedFile->getClientOriginalExtension();

            // Generate a unique filename based on the original filename, current date/time, and extension
            $currentDateTime = now()->format('Ymd-His');
            $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
            $input['profile'] = $uploadedFile->storeAs('userProfile', $filename, 'public');
        }
        $user->update($input);

        return response()->json(['message' => 'user updated successfully'], 200);
    }
}
