<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\MyNotification;
use App\Models\Chats;
use App\Models\OrderManagement;
use App\Models\TaskManagement;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function notificationsList()
    {
        $user = Auth::user();
        $notificationSettings = NotificationSetting::where('user_id', $user->id)->first();

        if ($notificationSettings && $notificationSettings->webTask === "false" && $notificationSettings->webChat === "false") {
            return response()->json(['message' => 'No notifications are allowed.']);
        }

        // Calculate the start and end of the current week
        $startOfWeek = now()->startOfWeek(); // Assuming you want the week to start on Monday
        $endOfWeek = now()->endOfWeek();

        $notificationsQuery = Notification::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->orderBy('id', 'desc');

        if ($notificationSettings) {
            if ($notificationSettings->webTask === "true") {
                $notificationsQuery->orWhere('type', 'TaskAssigned');
            }
            if ($notificationSettings->webChat === "true") {
                $notificationsQuery->where('type', 'Chat');
            }
        }

        $notifications = $notificationsQuery->get();

        foreach ($notifications as $notifc) {
            $notifc->update(['read_at' => 1]);
            if ($notifc->type == 'TaskAssigned') {
                $task = TaskManagement::where('id', $notifc->notifiable_id)->first();
                if (!empty($task->order_id)) {
                    $orderData = OrderManagement::where('id', $task->order_id)->first();
                    if ($orderData) {
                        if ($orderData->created_by) {
                            $userData = User::where('id', $orderData->created_by)->first();
                            if ($userData) {
                                $task['name'] = $userData->fname . '' . $userData->lname;
                                $task['profile'] = $userData->profile;
                            } else {
                                $task['name'] = [];
                                $task['profile'] = [];
                            }
                        }
                        $task['Ordername'] = $orderData->order_name;
                    } else {
                        $task['Ordername'] = [];
                    }
                } else {
                    $task['Ordername'] = [];
                }
                $notifc['data'] = $task;
            }
            if ($notifc->type == 'Chat') {
                $user = Chats::where('id', $notifc->notifiable_id)->first();
                if ($user) {
                    if ($user->user_id) {
                        $userData = User::where('id', $user->user_id)->first();
                        if ($userData) {
                            $user['name'] = $userData->fname . '' . $userData->lname;
                            $user['profile'] = $userData->profile;
                        } else {
                            $user['name'] = [];
                            $user['profile'] = [];
                        }
                    }
                } else {
                    $user['name'] = [];
                    $user['profile'] = [];
                }
                $notifc['data'] = $user;
            }
        }
        $user = Auth::user();
        $count = Notification::where('user_id', $user->id)->where('read_at', 0)->count();

        return response()->json(['notifications' => $notifications, 'count' => $count], 200);
    }
}
