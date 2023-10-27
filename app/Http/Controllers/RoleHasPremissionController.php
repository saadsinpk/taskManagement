<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoleHasPermission;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import the Validator facade

class RoleHasPremissionController extends Controller
{
    public function premissionView($role_id)
    {
        $user = Auth::user();
        $permissionGroups = [];

        if (!empty($role_id)) {
            $permissions = RoleHasPermission::where('role_id', $role_id)->get();
        }
        return response()->json(['permission' => $permissions]);
    }

    public function premissionModuleView($role_id, $model)
    {
        $user = Auth::user();
        $permissionModules = [];

        if (!empty($role_id)) {
            $permissionModules = RoleHasPermission::where('role_id', $role_id)->where('model', $model)->get();
        }
        return response()->json(['permissionModule' => $permissionModules]);
    }

    public function premissionUpdate(Request $request)
    {
        $user = Auth::user();
        if (empty($request->titles)) {
            $existingRole = RoleHasPermission::where('role_id', $request->role_id)->where('model', $request->models[0])->get();

            if (!empty($existingRole)) {
                RoleHasPermission::where('role_id', $request->role_id)->where('model', $request->models[0])->delete();
            }
            return response()->json(['message' => 'Permissions given successfully'], 201);
        } else {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required',
                'titles' => 'required|array',
                'titles.*' => 'required|string',
                'models' => 'required|array',
                'models.*' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 401);
            }

            $input = $request->all();
            $role_id = $input['role_id'];
            $titles = $input['titles'];
            $models = $input['models'];

            $existingRole = RoleHasPermission::where('role_id', $role_id)->where('model', $models[0])->get();

            if (!empty($existingRole)) {
                RoleHasPermission::where('role_id', $role_id)->where('model', $models[0])->delete();
            }

            $createdPermissions = [];

            foreach ($titles as $key => $title) {
                $permissionData = RoleHasPermission::create([
                    'role_id' => $role_id,
                    'title' => $title,
                    'model' => $models[$key],
                ]);
                $createdPermissions[] = $title;
            }

            if (!empty($createdPermissions)) {
                return response()->json(['message' => 'Permissions given successfully'], 201);
            } else {
                return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
            }
        }
    }
}
