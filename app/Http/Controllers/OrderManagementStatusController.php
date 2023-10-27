<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderManagementStatus;
use App\Models\OrderManagement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderManagementStatusController extends Controller
{
    public function orderStatusCreate(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'status_name' => 'required',
            'background' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $taskmngstatus = OrderManagementStatus::create($input);
        if ($taskmngstatus) {
            return response()->json(['message' => 'Status created successfully'], 201);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function orderStatusList()
    {
        $status = OrderManagementStatus::all();
        return response()->json(['status' => $status], 200);
    }

    public function orderStatusEdit($id)
    {
        $status = OrderManagementStatus::where('id', $id)->first(); // Use first() to retrieve a single record
        if (!$status) {
            return response()->json(['message' => 'status not found'], 404);
        }

        return response()->json(['status' => $status], 200);
    }

    public function orderStatusUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required', // Validate that the 'id' exists in the 'users' table
        ]);

        $status = OrderManagementStatus::find($request->id); // Use 'find()' to retrieve a single record by primary key

        if (!$status) {
            return response()->json(['message' => 'status not found'], 404);
        }

        $status->update($request->all());

        return response()->json(['message' => 'status updated successfully'], 200);
    }

    public function orderStatusDelete($id, $newID)
    {
        $status = OrderManagementStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'status not found'], 404);
        }

        $delete = $status->delete();
        if ($delete) {
            OrderManagement::where('order_status', $id)->update(['order_status' => $newID]);
            return response()->json(['message' => 'status deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to delete status'], 500);
        }
    }
}
