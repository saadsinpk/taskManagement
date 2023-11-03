<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chats;
use App\Events\sendMessage;
use App\Events\MessageNotifaction;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\TaskManagement;
use App\Models\OrderManagement;
use App\Notifications\MyNotification;
use App\Events\NewNotificationEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Mail\sendMail;
use Illuminate\Support\Facades\Mail;

class ChatsController extends Controller
{
    public function messageRecevied()
    {
        $user = Auth::user();
        $notifaction = Chats::with('user')->where('user_chat', $user->id)->where('is_real', '0')->get();
        // event(new MessageNotifaction($user, $notifaction));
        return $notifaction;
    }

    public function messageSend(Request $request)
    {
        $userData = Auth::user();
        $messageSender = Chats::create([
            'user_id' => $userData->id,
            'user_chat' => $request->user_chat,
            'message' => $request->message,
            'is_read' => false,
            // 'senderName' = $message->user_id == Auth::user()->id ? 'me' : 'other',
        ]);
        Notification::create([
            'user_id' => $request->user_chat,
            'type' => 'Chat',
            'notifiable_id' => $messageSender->id,
            'notifiable_type' => Chats::class,
            'data' =>  $request->message,
            'read_at' => '0',
        ]);
        $received_id = $request->user_chat;
        $received = intval($request->user_chat);

        $message = Chats::where(function ($query) use ($userData, $received_id) {
            $query->where('user_id', $userData->id)
                ->where('user_chat', $received_id);
        })->orWhere(function ($query) use ($userData, $received_id) {
            $query->where('user_id', $received_id)
                ->where('user_chat', $userData->id);
        })->orderBy('created_at', 'desc')->limit(15)->get();

        $customData =  'messageUpdate';

        $userRecentC = $this->messageConversationData($userData->id);
        $otherRecentChats = $this->messageConversationData($received_id);

        $userSenderList = [
            $SenderList =  ['id' => $userData->id, 'list' => $userRecentC],
            $receiverList = ['id' => $received, 'list' => $otherRecentChats],
        ];
        // Trigger the event
        event(new sendMessage($userData->id, $message, $customData, $userSenderList, $received));

        $count = 0;
        $notificationSettings = NotificationSetting::where('user_id', $userData->id)->first();
        if ($notificationSettings) {
            if ($notificationSettings && $notificationSettings->webTask === "false" && $notificationSettings->webChat === "false") {
                $count = 0;
            } else { // Calculate the start and end of the current week
                $startOfWeek = now()->startOfWeek(); // Assuming you want the week to start on Monday
                $endOfWeek = now()->endOfWeek();

                $noifiactionsCount = Notification::where('user_id', $userData->id)
                    ->where('read_at', 0)
                    ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);

                if ($notificationSettings->webTask === "true" && $notificationSettings->webChat === "true") {
                    $count = $noifiactionsCount->count();
                } else {
                    if ($notificationSettings->webTask === "true") {
                        $noifiactionsCount->where('type', 'TaskAssigned');
                    }
                    if ($notificationSettings->webChat === "true") {
                        $noifiactionsCount->where('type', 'Chat');
                    }
                    $count = $noifiactionsCount->count();
                }
            }
        }

        $userNotificationData = [
            'id' => $received,
            'count' => $count,
        ];

        $user = Auth::user();
        $userAssignedTask = User::find($received_id);
        $checkMail = NotificationSetting::where('user_id', $user->id)->where('emailChat', 'true')->first();
        if ($checkMail) {
            // Send the notification email
            $res = Mail::to($userAssignedTask->email)->send(new sendMail($messageSender));
        }
        $checksssss = NotificationSetting::where('user_id', $user->id)->where('webChat', 'true')->first();
        if ($checksssss) {
            // Trigger the event
            event(new NewNotificationEvent($userNotificationData));
        }

        return $message;
    }

    public function messageHistory($received_id)
    {
        $user = Auth::user();

        $messages = Chats::where(function ($query) use ($user, $received_id) {
            $query->where('user_id', $user->id)
                ->where('user_chat', $received_id);
        })->orWhere(function ($query) use ($user, $received_id) {
            $query->where('user_id', $received_id)
                ->where('user_chat', $user->id);
        })->orderBy('created_at', 'desc')->limit(15)->get();

        if ($messages->count() > 0) {
            $messages->each(function ($message) {
                $message->update(['is_read' => true]);
            });
        }
        $count = $messages->count();
        $userRecentC = $this->messageConversationData($user->id);
        $msg = ['messages' => $messages, 'count' => $count, 'recentChats' => $userRecentC];

        return $msg;
    }

    public function messageHistoryLoder(Request $request)
    {
        $user = Auth::user();
        $received_id = $request->user_id;
        $messagesCount = Chats::where(function ($query) use ($user, $received_id) {
            $query->where('user_id', $user->id)
                ->where('user_chat', $received_id);
        })->orWhere(function ($query) use ($user, $received_id) {
            $query->where('user_id', $received_id)
                ->where('user_chat', $user->id);
        })->orderBy('created_at', 'desc')->count();

        if ($messagesCount != $request->total_chats) {
            $setLimit = $request->total_chats + 10;

            $messages = Chats::where(function ($query) use ($user, $received_id) {
                $query->where('user_id', $user->id)
                    ->where('user_chat', $received_id);
            })->orWhere(function ($query) use ($user, $received_id) {
                $query->where('user_id', $received_id)
                    ->where('user_chat', $user->id);
            })->orderBy('created_at', 'desc')->limit($setLimit)->get();

            if ($messages->count() > 0) {
                $messages->each(function ($message) {
                    $message->update(['is_read' => true]);
                });
            }
            $count = $messages->count();
            $msg = ['messages' => $messages, 'count' => $count];
        } else {
            $msg = [];
        }


        return $msg;
    }

    public function messageConversation()
    {
        $userLogin = Auth::user();
        $userId = $userLogin->id;

        if ($userId != 1) {
            $superAdminId = 1;

            $taskOrderIds = TaskManagement::where('created_by', $userId)
                ->orWhere(function ($query) use ($userId) {
                    $query->where('collaboration', 'LIKE', $userId)
                        ->orWhere('collaboration', 'LIKE', $userId . ',%')
                        ->orWhere('collaboration', 'LIKE', '%,' . $userId . ',%')
                        ->orWhere('collaboration', 'LIKE', '%,' . $userId);
                })
                ->get()
                ->pluck('order_id');

            $orderIdsCreatedByUser = OrderManagement::where('created_by', $userId)
                ->pluck('id');

            $orderIds = $taskOrderIds->merge($orderIdsCreatedByUser)->unique();

            $userInfo = TaskManagement::whereIn('order_id', $orderIds)
                ->where('collaboration', 'LIKE', '%' . $userId . '%')
                ->pluck('collaboration')
                ->map(function ($assignedTo) {
                    return explode(',', $assignedTo); // Split the string by comma to get an array of user IDs
                })
                ->flatten()
                ->unique()
                ->reject(function ($userId) use ($userLogin) {
                    return $userId == $userLogin->id;
                });

            if (!$userInfo->contains($superAdminId)) {
                if ($userId != 1) {
                    $userInfo->push($superAdminId);
                }
            }

            $users = User::whereIn('id', $userInfo)->get();
        } else {
            $users = User::where('id', '!=', $userLogin->id)->get();
        }
        $recentChats = [];

        foreach ($users as $user) {
            $check = Chats::where('user_id', $user->id)->orwhere('user_chat', $user->id)->count();
            if ($check > 0) {

                // $last_message = Chats::where('user_id', $userLogin->id)
                //     ->Orwhere('user_chat', $user->id)
                //     ->orderBy('created_at', 'desc')
                //     ->first();
                $last_message = Chats::where(function ($query) use ($userLogin, $user) {
                    $query->where('user_id', $userLogin->id)
                        ->where('user_chat', $user->id);
                })->orWhere(function ($query) use ($userLogin, $user) {
                    $query->where('user_id', $user->id)
                        ->where('user_chat', $userLogin->id);
                })->orderBy('created_at', 'desc')->first();

                $count = Chats::where('user_chat', $userLogin->id)->where('user_id', $user->id)
                    ->where('is_read', 0)
                    ->count();
                if ($last_message) {
                    $last = $last_message->message;
                    $time = $last_message->created_at;
                    $last_id = $last_message->id;
                } else {
                    $last = null;
                    $time = null;
                    $count = null;
                    $last_id = null;
                }
            } else {
                $time = null;
                $last = null;
                $count = null;
                $last_id = null;
            }
            $recentChats[] = [
                'user_id' => $user->id,
                'user_name' => $user->fname . ' ' . $user->lname,
                'user_profile' => $user->profile,
                'last_Id' => $last_id,
                'last_message' =>  $last,
                'count' => $count,
                'timestamp' => $time,
            ];
        }

        // Sort the array by 'timestamp' key in descending order
        usort($recentChats, function ($a, $b) {
            return ($a['timestamp'] < $b['timestamp']) ? 1 : -1;
        });

        return $recentChats;
    }

    public function messageSeen(Request $request)
    {
        $msgID = $request->msgID;
        $userID = $request->userID;
        $userData = Auth::user();
        $messages = Chats::find($msgID);
        if ($messages) {
            $messages->update(['is_read' => 1]);
            $userRecentC = $this->messageConversationData($userData->id);
            return $userRecentC;
        } else {
            return response()->json(['error' => true]);
        }
    }

    private function messageConversationData($id)
    {
        $userLogin = $id;
        $userId = $id;

        if ($userId != 1) {
            $superAdminId = 1;

            $taskOrderIds = TaskManagement::where('assigned_to', $userId)
                ->pluck('order_id');

            $orderIdsCreatedByUser = OrderManagement::where('created_by', $userId)
                ->pluck('id');

            $orderIds = $taskOrderIds->merge($orderIdsCreatedByUser)->unique();

            $userInfo = TaskManagement::whereIn('order_id', $orderIds)
                ->pluck('assigned_to')
                ->unique()
                ->reject(function ($userId) use ($userLogin) {
                    return $userId == $userLogin;
                });

            if (!$userInfo->contains($superAdminId)) {
                if ($userId != 1) {
                    $userInfo->push($superAdminId);
                }
            }

            $users = User::whereIn('id', $userInfo)
                ->get();
        } else {
            $users = User::where('id', '!=', $userLogin)->get();
        }
        $recentChats = []; // Initialize an empty array to store the conversation data

        foreach ($users as $user) {
            $check = Chats::where('user_id', $user->id)->orwhere('user_chat', $user->id)->count();
            if ($check > 0) {

                // $last_message = Chats::where('user_id', $userLogin->id)
                //     ->Orwhere('user_chat', $user->id)
                //     ->orderBy('created_at', 'desc')
                //     ->first();
                $last_message = Chats::where(function ($query) use ($userLogin, $user) {
                    $query->where('user_id', $userLogin)
                        ->where('user_chat', $user->id);
                })->orWhere(function ($query) use ($userLogin, $user) {
                    $query->where('user_id', $user->id)
                        ->where('user_chat', $userLogin);
                })->orderBy('created_at', 'desc')->first();

                $count = Chats::where('user_chat', $userLogin)->where('user_id', $user->id)
                    ->where('is_read', 0)
                    ->count();
                if ($last_message) {
                    $last = $last_message->message;
                    $time = $last_message->created_at;
                    $last_id = $last_message->id;
                } else {
                    $last = null;
                    $time = null;
                    $count = null;
                    $last_id = null;
                }
            } else {
                $time = null;
                $last = null;
                $count = null;
                $last_id = null;
            }
            $recentChats[] = [
                'user_id' => $user->id,
                'user_name' => $user->fname . ' ' . $user->lname,
                'user_profile' => $user->profile,
                'last_Id' => $last_id,
                'last_message' =>  $last,
                'count' => $count,
                'timestamp' => $time,
            ];
        }

        // Sort the array by 'timestamp' key in descending order
        usort($recentChats, function ($a, $b) {
            return ($a['timestamp'] < $b['timestamp']) ? 1 : -1;
        });

        return $recentChats;
    }

    private function notificationsData($id)
    {
        $user = $id;
        $notificationSettings = NotificationSetting::where('user_id', $user)->first();

        if ($notificationSettings && $notificationSettings->webTask === "false" && $notificationSettings->webChat === "false") {
            return false;
        }

        // Calculate the start and end of the current week
        $startOfWeek = now()->startOfWeek(); // Assuming you want the week to start on Monday
        $endOfWeek = now()->endOfWeek();

        $notificationsQuery = Notification::where('user_id', $user)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->orderBy('id', 'desc');

        if ($notificationSettings) {
            if ($notificationSettings->webTask === "true" && $notificationSettings->webChat === "true") {
                $notifications = $notificationsQuery->get();
            } else {
                if ($notificationSettings->webTask === "true") {
                    $notificationsQuery->where('type', 'TaskAssigned');
                }
                if ($notificationSettings->webChat === "true") {
                    $notificationsQuery->where('type', 'Chat');
                }
                $notifications = $notificationsQuery->get();
            }
        }

        foreach ($notifications as $notifc) {
            if ($notificationSettings->webTask === "true") {
                if ($notifc->type == 'TaskAssigned') {
                    $task = TaskManagement::where('id', $notifc->notifiable_id)->first();
                    if ($task) {
                        // Task record exists
                        if ($task->order_id) {
                            $orderData = OrderManagement::where('id', $task->order_id)->first();

                            if ($orderData) {
                                $task['name'] = $orderData->order_name;
                            } else {
                                $task['name'] = null;
                            }
                        } else {
                            $task['name'] = null;
                        }
                    } else {
                        // Task record doesn't exist
                        $task = ['name' => null];
                    }
                    $notifc['data'] = $task;
                }
            }
            if ($notificationSettings->webChat === "true") {
                if ($notifc->type == 'Chat') {
                    $userChat = Chats::where('id', $notifc->notifiable_id)->first();
                    if (!empty($userChat->user_id)) {
                        $userData = User::where('id', $userChat->user_id)->first();
                        if ($userData) {
                            $userChat['name'] = $userData->fname . '' . $userData->lname;
                        } else {
                            $userChat['name'] = [];
                        }
                    }
                    $notifc['data'] = $user;
                }
            }
        }

        return $notifications;
    }
}
