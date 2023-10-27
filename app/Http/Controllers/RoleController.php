<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RoleHasPermission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class RoleController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();
        $validate = $request->validate([
            'name' => 'required',
        ]);
        if (empty($validate)) {
            return response()->json(['message' => 'required field'], 401);
        }

        $role = Role::create([
            'name' => $request->name,
            'created_by' => $user->id
        ]);
        if ($role) {
            // Role creation was successful
            return response()->json(['message' => 'Role created successfully'], 201);
        } else {
            // Role creation failed
            return response()->json(['message' => 'Role creation failed'], 500);
        }
    }

    public function list()
    {
        $roles = Role::all(); // Retrieve all roles from the "roles" table

        // You can now work with the $roles collection
        return response()->json(['roles' => $roles], 200);
    }

    public function edit($id)
    {
        $role = Role::where('id', $id)->first(); // Use first() to retrieve a single record
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json(['role' => $role], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:roles,id', // Validate that the 'id' exists in the 'roles' table
            'name' => 'required',
        ]);

        $role = Role::find($request->id); // Use 'find()' to retrieve a single record by primary key

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->update(['name' => $request->name]);

        return response()->json(['message' => 'Role updated successfully'], 200);
    }

    public function delete($id, $newID)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $delete = $role->delete();

        if ($delete) {
            // Update users with the old role ID to use the new role ID
            User::where('role', $id)->update(['role' => $newID]);
            RoleHasPermission::where('role_id', $id)->delete();

            return response()->json(['message' => 'Role deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to delete role'], 500);
        }
    }
}
