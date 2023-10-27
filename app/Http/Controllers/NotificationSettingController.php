<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationSettingController extends Controller
{
    public function notificationSettingView()
    {
        $user = Auth::user();
        $notifactionSetting = NotificationSetting::where('user_id', $user->id)->get();
        return response()->json(['notifactionSetting' => $notifactionSetting], 200);
    }

    public function notificationSettingUpdate(Request $request)
    {
        $user = Auth::user();

        $notifactionSetting = NotificationSetting::where('user_id', $user->id)->first();

        if ($notifactionSetting) {
            // $request->validate([
            //     'id' => 'required',
            // ]);

            $notifactionSetting->update($request->all());
        } else {
            $notifactionSetting = NotificationSetting::create([
                'user_id' => $user->id,
                'emailTask' => $request->emailTask,
                'emailChat' => $request->emailChat,
                'webTask' => $request->webTask,
                'webChat' => $request->webChat,
            ]);
        }

        return response()->json(['message' => 'NotifactionSetting note updated or created', 'NotifactionSettingNote' => $notifactionSetting], 200);
    }
}
