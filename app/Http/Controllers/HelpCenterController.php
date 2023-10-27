<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\HelpCenter;

class HelpCenterController extends Controller
{
    public function helpCenterCreate(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'paragraph' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['created_by'] = $user->id;
        $helpCenter = HelpCenter::create($input);
        if ($helpCenter) {
            return response()->json(['message' => 'Help Center created successfully'], 201);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function helpCenterList()
    {
        $helpCenter = HelpCenter::all();

        return response()->json(['helpCenter' => $helpCenter], 200);
    }

    public function helpCenterEdit($id)
    {
        $helpCenter = HelpCenter::where('id', $id)->first();

        if (!$helpCenter) {
            return response()->json(['message' => 'Help Center not found'], 404);
        }

        return response()->json(['HelpCenter' => $helpCenter], 200);
    }

    public function helpCenterUpdate(Request $request)
    {

        $request->validate([
            'id' => 'required', // Validate that the 'id' exists in the 'users' table
        ]);

        $helpCenter = HelpCenter::find($request->id); // Use 'find()' to retrieve a single record by primary key

        if (!$helpCenter) {
            return response()->json(['message' => 'Help Center not found'], 404);
        }

        $input = $request->all();
        $helpCentermng = $helpCenter->update($input);
        if ($helpCentermng) {
            return response()->json(['message' => 'Help Center updated successfully'], 200);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function helpCenterDelete($id)
    {
        $helpCenter = HelpCenter::find($id);

        if (!$helpCenter) {
            return response()->json(['message' => 'Help Center not found'], 404);
        }

        $helpCenter->delete();

        return response()->json(['message' => 'Help Center deleted successfully'], 200);
    }
}
