<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\GeneralSetting;

class GeneralSettingController extends Controller
{
    public function generalSetting()
    {
        $user = Auth::user();
        $generalSetting = GeneralSetting::where('user_id', $user->id)->first();
        if ($generalSetting !== null) {
            return response()->json(['generalSetting' => $generalSetting], 200);
        } else {
            return response()->json(['message' => 'Data not found'], 200);
        }
    }

    public function generalSettingUpdate(Request $request)
    {
        $user = Auth::user();
        $generalSetting = GeneralSetting::where('user_id', $user->id)->first();
        if (!empty($generalSetting)) {
            $input = $request->all();
            if (isset($input['favicon'])) {
                $uploadedFile = $request->file('favicon');
                $originalFilename = 'favicon';

                // Get the file extension
                $extension = $uploadedFile->getClientOriginalExtension();

                // Generate a unique filename based on the original filename, current date/time, and extension
                $currentDateTime = now()->format('Ymd-His');
                $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
                $input['favicon'] = $uploadedFile->storeAs('generalsetting', $filename, 'public');
            }
            if (isset($input['footer_logo'])) {
                $uploadedFile = $request->file('footer_logo');
                $originalFilename = 'footer_logo';

                // Get the file extension
                $extension = $uploadedFile->getClientOriginalExtension();

                // Generate a unique filename based on the original filename, current date/time, and extension
                $currentDateTime = now()->format('Y-m-d_H-i-s');
                $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
                $input['footer_logo'] = $uploadedFile->storeAs('generalsetting', $filename, 'public');
            }
            $generalSettingData = $generalSetting->update($input);
            if ($generalSettingData) {
                return response()->json(['message' => 'General Setting updated successfully'], 200);
            } else {
                return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
            }
        } else {
            $input = $request->all();
            if (isset($input['favicon'])) {
                $uploadedFile = $request->file('favicon');
                $originalFilename = 'favicon';

                // Get the file extension
                $extension = $uploadedFile->getClientOriginalExtension();

                // Generate a unique filename based on the original filename, current date/time, and extension
                $currentDateTime = now()->format('Ymd-His');
                $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
                $input['favicon'] = $uploadedFile->storeAs('generalsetting', $filename, 'public');
            }
            if (isset($input['footer_logo'])) {
                $uploadedFile = $request->file('footer_logo');
                $originalFilename = 'footer_logo';

                // Get the file extension
                $extension = $uploadedFile->getClientOriginalExtension();

                // Generate a unique filename based on the original filename, current date/time, and extension
                $currentDateTime = now()->format('Y-m-d_H-i-s');
                $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
                $input['footer_logo'] = $uploadedFile->storeAs('generalsetting', $filename, 'public');
            }
            $input['user_id'] = $user->id;
            $generalSettingData = GeneralSetting::create($input);
            if ($generalSettingData) {
                return response()->json(['message' => 'General Setting updated successfully'], 200);
            } else {
                return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
            }
        }
    }
}
