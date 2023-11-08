<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\TaskManagement;
use App\Models\User;
use App\Models\TaskManagementStatus;
use App\Models\OrderManagement;
use App\Models\TaskComment;
use App\Models\TaskActivity;
use App\Models\History;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\Role;
use App\Models\ProjectOverview;
use App\Events\NewNotificationEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import the Validator facade
use App\Mail\sendMail;
use Illuminate\Support\Facades\Mail;

class TaskManagementController extends Controller
{
    public function taskCreateData()
    {
        $user = User::all();
        $status = TaskManagementStatus::all();
        $order = OrderManagement::all();
        return response()->json(['assignedTo' => $user, 'status' => $status, 'order' => $order]);
    }

    public function taskCreate(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'priority' => 'required',
            'status' => 'required',
            'deadline' => 'required',
            'description' => 'required',
            'order_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['created_by'] = $user->id;
        $input['assigned_to'] = $user->id;
        $input['start_date'] = now();
        $taskmngstatus = TaskManagement::create($input);

        $assignedToIDs = explode(',', $input['collaboration']);

        foreach ($assignedToIDs as $assignedToID) {
            Notification::create([
                'user_id' => $assignedToID,
                'type' => 'TaskAssigned',
                'notifiable_id' => $taskmngstatus->id,
                'notifiable_type' => TaskManagement::class,
                'data' =>  $input['order_id'],
                'read_at' => '0',
            ]);
            $userWhoAssigned = Auth::user(); // User who assigns the task
            $userAssignedTask = User::find($input['assigned_to']); // User to whom the task is assigned
            $checkMail = NotificationSetting::where('user_id', $user->id)->where('emailTask', 'true')->first();
            if ($checkMail) {
                // Send the notification email
                $res = Mail::to($userAssignedTask->email)->send(new sendMail($taskmngstatus));
            }
            $received = intval($assignedToID);

            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $count = Notification::where('user_id', $received)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->where('read_at', 0)->count();
            $userNotificationData = [
                'id' => $received,
                'count' => $count,
            ];
            $user = Auth::user();
            $check = NotificationSetting::where('user_id', $user->id)->where('webTask', 'true')->first();
            if ($check) {
                // Trigger the event
                event(new NewNotificationEvent($userNotificationData));
            }
        }




        if ($taskmngstatus) {
            return response()->json(['message' => 'Task created successfully'], 201);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function taskList()
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $task = TaskManagement::all();
        } else {
            $task = TaskManagement::where('created_by', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('collaboration', 'LIKE', $user->id)
                        ->orWhere('collaboration', 'LIKE', $user->id . ',%')
                        ->orWhere('collaboration', 'LIKE', '%,' . $user->id . ',%')
                        ->orWhere('collaboration', 'LIKE', '%,' . $user->id);
                })
                ->get();
        }
        foreach ($task as $tsk) {
            if ($tsk->status) {
                $data = TaskManagementStatus::select('status_name')->where('id', $tsk->status)->first();
                if ($data !== null) {
                    $tsk['statusName'] = $data->status_name;
                }
            }
            if ($tsk->assigned_to) {
                $users = User::select('fname')->where('id', $tsk->assigned_to)->first();
                if ($users) {
                    $tsk['assigned_to_name'] = $users->fname;
                } else {
                    $tsk['assigned_to_name'] = 'SuperAdmin';
                }
            }
            if ($tsk->order_id) {
                $orderDta = OrderManagement::select('order_name')->where('id', $tsk->order_id)->first();
                if ($orderDta) {
                    $tsk['order_name'] = $orderDta->order_name;
                } else {
                    $tsk['order_name'] = [];
                }
            }
            if ($tsk->overview) {
                $orderDta = ProjectOverview::select('name')->where('id', $tsk->overview)->first();
                if ($orderDta) {
                    $tsk['overview_name'] = $orderDta->name;
                } else {
                    $tsk['overview_name'] = [];
                }
            }
            // if ($tsk->collaboration) {
            //     $userDetails = [];
            //     $collaborationIds = explode(',', $tsk->collaboration);
            //     $users = User::whereIn('id', $collaborationIds)->get();
            //     if ($users) {
            //         foreach ($users as $user) {
            //             $userDetails[] = [
            //                 'id' => $user->id,
            //                 'name' => $user->fname . ' ' . $user->lname,
            //                 'email' => $user->email,
            //                 'profile' => $user->profile,
            //             ];
            //         }
            //     }
            //     $task['userDetails'] = $userDetails;
            // }
        }
        return response()->json(['task' => $task], 200);
    }

    public function taskEdit($id)
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $task = TaskManagement::where('id', $id)->first();
        } else {
            $task = TaskManagement::where('id', $id)
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                        ->orWhere('collaboration', 'LIKE', '%' . $user->id . '%');
                })
                ->first();
        }
        if (!$task) {
            return response()->json(['message' => 'task not found'], 404);
        }

        if ($task->status) {
            $data = TaskManagementStatus::select('status_name')->where('id', $task->status)->first();
            if ($data !== null) {
                $task['statusName'] = $data->status_name;
            }
        }
        if ($task->assigned_to) {
            $users = User::select('fname')->where('id', $task->assigned_to)->first();
            $task['assigned_to_name'] = $users->fname;
        }
        if ($task->order_id) {
            $orderDta = OrderManagement::where('id', $task->order_id)->first();
            if ($orderDta) {
                $task['orderData'] = $orderDta;
            } else {
                $task['orderData'] = [];
            }
        }
        if ($task->collaboration) {
            $userDetails = [];
            $collaborationIds = explode(',', $task->collaboration);
            $users = User::whereIn('id', $collaborationIds)->get();
            if ($users) {
                foreach ($users as $user) {
                    $userDetails[] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'email' => $user->email,
                        'profile' => $user->profile,
                    ];
                }
            }
            $task['userDetails'] = $userDetails;
        }
        if ($task->collaboration) {
            $userDetails = [];
            $collaborationIDs = explode(',', $task->collaboration);
            $collaborationIDs = array_map('intval', $collaborationIDs);
            $task['arrayIDS'] = $collaborationIDs;
        }
        $user = User::all();
        $status = TaskManagementStatus::all();
        $order = OrderManagement::all();

        return response()->json(['task' => $task, 'user' => $user, 'status' => $status, 'order' => $order], 200);
    }

    public function taskView($id)
    {
        $history = [];
        $user = Auth::user();
        $history = History::where('task_id', $id)->orderBy('created_at', 'desc')->get();
        if ($user->id == 1) {
            $task = TaskManagement::where('id', $id)->first();
        } else {
           
            // $task = TaskManagement::where('id', $id)
            //     ->where(function ($query) use ($user) {
            //         $query->where('created_by', $user->id)
            //             ->orWhere('collaboration', 'LIKE', '%' . $user->id . '%');
            //     })
            //     ->first();
            $task = TaskManagement::where('id', $id)->first();
        }
        if (!$task) {
            return response()->json(['message' => 'task not found'], 404);
        }

        if ($task->status) {
            $data = TaskManagementStatus::select('status_name')->where('id', $task->status)->first();
            if ($data !== null) {
                $task['statusName'] = $data->status_name;
            }
        }
        if ($task->assigned_to) {
            $users = User::where('id', $task->assigned_to)->first();
            $task['assigned_to_name'] = $users->fname;
            $task['assigned_to_picture'] = $users->profile;
            $role = Role::where('id', $users->role)->get();
            $task['roleData'] = $role;
        }
        if ($task->order_id) {
            $orderDta = OrderManagement::where('id', $task->order_id)->first();
            if ($orderDta) {
                $task['orderData'] = $orderDta->order_name;
            } else {
                $task['orderData'] = [];
            }
        }
        if ($task->collaboration) {
            $userDetails = [];
            $collaborationIds = explode(',', $task->collaboration);
            $users = User::whereIn('id', $collaborationIds)->get();
            if ($users) {
                foreach ($users as $user) {
                    $userDetails[] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'email' => $user->email,
                        'profile' => $user->profile,
                    ];
                }
            }
            $task['userDetails'] = $userDetails;
        }
        if ($task->collaboration) {
            $userDetails = [];
            $collaborationIDs = explode(',', $task->collaboration);
            $collaborationIDs = array_map('intval', $collaborationIDs);
            $task['arrayIDS'] = $collaborationIDs;
        }

        foreach ($history as $hstData) {
            if ($hstData->user_id) {
                $userData = User::find($hstData->user_id);
                if (!empty($userData)) {
                    $hstData['userData'] = $userData;
                } else {
                    $hstData['userData'] = [];
                }
            }
            if ($hstData->task_id) {
                $taskData = TaskManagement::find($hstData->task_id);
                if (!empty($taskData)) {
                    $hstData['taskData'] = $taskData;
                    if ($hstData->colume_name == 'order_id') {
                        $odr = OrderManagement::find($hstData->old_data);
                        if (!empty($odr)) {
                            $hstData['old_data'] = $odr->order_name;
                        } else {
                            $hstData['old_data'] = [];
                        }
                        $odr = OrderManagement::find($hstData->new_data);
                        if (!empty($odr)) {
                            $hstData['new_data'] = $odr->order_name;
                        } else {
                            $hstData['new_data'] = [];
                        }
                    }
                    if ($hstData->colume_name == 'assigned_to') {
                        $userInfo = User::find($hstData->old_data);
                        if (!empty($userInfo)) {
                            $hstData['old_data'] = $userInfo->fname . '' . $userInfo->lname;
                        } else {
                            $hstData['old_data'] = [];
                        }
                        $userInfo = User::find($hstData->new_data);
                        if (!empty($userInfo)) {
                            $hstData['new_data'] = $userInfo->fname . '' . $userInfo->lname;
                        } else {
                            $hstData['new_data'] = [];
                        }
                    }
                    if ($hstData->colume_name == 'status') {
                        $taskStatus = TaskManagementStatus::find($hstData->old_data);
                        if (!empty($taskStatus)) {
                            $hstData['old_data'] = $taskStatus->status_name;
                        } else {
                            $hstData['old_data'] = [];
                        }
                        $taskStatus = TaskManagementStatus::find($hstData->new_data);
                        if (!empty($taskStatus)) {
                            $hstData['new_data'] = $taskStatus->status_name;
                        } else {
                            $hstData['new_data'] = [];
                        }
                    }
                    if ($hstData->colume_name == 'collaboration') {
                        $collaborationIds = explode(',', $hstData->collaboration);

                        // Retrieve old_data for each user in collaborationIds
                        $oldData = [];
                        $oldUsers = User::whereIn('id', $collaborationIds)->get();
                        foreach ($oldUsers as $user) {
                            $oldData[] = [
                                'id' => $user->id,
                                'name' => $user->fname . ' ' . $user->lname,
                                'email' => $user->email,
                                'profile' => $user->profile,
                            ];
                        }

                        // Retrieve new_data for each user in collaborationIds
                        $newData = [];
                        $newUsers = User::whereIn('id', $collaborationIds)->get();
                        foreach ($newUsers as $user) {
                            $newData[] = [
                                'id' => $user->id,
                                'name' => $user->fname . ' ' . $user->lname,
                                'email' => $user->email,
                                'profile' => $user->profile,
                            ];
                        }

                        // Assign the retrieved data to corresponding properties
                        $hstData['old_data'] = $oldData;
                        $hstData['new_data'] = $newData;
                    }
                } else {
                    $hstData['taskData'] = [];
                }
            }
        }

        $taskComnt = TaskComment::where('task_id', $id)->get();

        foreach ($taskComnt as $key => $cmnt) {
            if ($cmnt->user_id) {
                $user = User::where('id', $cmnt->user_id)->first();
                if ($user) {
                    $taskComnt[$key]['userInfo'] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'profile' => $user->profile
                    ];
                } else {
                    $taskComnt[$key]['userInfo'] = [];
                }
            }
        }

        $taskActivity = TaskActivity::where('task_id', $id)->orderBy('id', 'Desc')->get();
       
        foreach ($taskActivity as $activity) {
            $totalDurationInMinutes = 0;
            $checkInTime = strtotime($activity->checkIn);
            $checkOutTime = strtotime($activity->checkOut);
        
            $timeDifference = $checkOutTime - $checkInTime;
            $durationInMinutes = $timeDifference / 60;
        
            $totalDurationInMinutes += $durationInMinutes;
            $totalHours = floor($totalDurationInMinutes / 60);
            $totalMinutes = $totalDurationInMinutes % 60;
             
            $activity->total_time = $totalHours .' : '. $totalMinutes;
        }
    


        foreach ($taskActivity as $key => $value) {
            if ($value->user_id) {
                $user = User::where('id', $value->user_id)->first();
                if ($user) {
                    $taskActivity[$key]['userInfo'] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'profile' => $user->profile
                    ];
                } else {
                    $taskActivity[$key]['userInfo'] = [];
                }
            }
        }
        $user = Auth::user();
        $taskCheckActivity = TaskActivity::where('task_id', $id)
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->first();
        if ($taskCheckActivity) {
            if ($taskCheckActivity->checkOut === null) {
                $taskChkActivities = [
                    'check' => false,
                    'id' => $taskCheckActivity->id,
                ];
            } else {
                $taskChkActivities = [
                    'check' => true,
                ];
            }
        } else {
            $taskChkActivities = [
                'check' => true,
            ];
        }
        return response()->json(['task' => $task, 'history' => $history, 'taskComment' => $taskComnt, 'taskActivity' => $taskActivity, 'taskCheckActivity' => $taskChkActivities], 200);
    }

    public function taskUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $task = TaskManagement::find($request->id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $oldData = $task->toArray();

        $task->update($request->all());

        $newData = $task->toArray();

        foreach ($newData as $key => $value) {
            if ($oldData[$key] !== $value) {
                if ($key !== 'updated_at') {
                    History::create([
                        'user_id' => Auth::id(),
                        'task_id' => $task->id,
                        'field' => $key,
                        'old_data' => $oldData[$key],
                        'new_data' => $value,
                        'colume_name' => $key,
                    ]);
                }
                if ($key == 'collaboration') {
                    $oldCollaboration = explode(',', $oldData[$key]);
                    $newCollaboration = explode(',', $value);

                    $addedCollaborators = array_diff($newCollaboration, $oldCollaboration);

                    foreach ($addedCollaborators as $addedCollaborator) {
                        Notification::create([
                            'user_id' => $addedCollaborator,
                            'type' => 'TaskAssigned',
                            'notifiable_id' => $task->id,
                            'notifiable_type' => TaskManagement::class,
                            'data' => $task->order_id,
                            'read_at' => '0',
                        ]);
                        $count = Notification::where('user_id', $addedCollaborator)->where('read_at', 0)->count();

                        $userNotificationData = [
                            'id' => intval($addedCollaborator),
                            'count' => $count,
                        ];
                        $userAssignedTask = User::find($addedCollaborator);
                        $checkMail = NotificationSetting::where('user_id', $addedCollaborator)->where('emailTask', 'true')->first();
                        if ($checkMail) {
                            // Send the notification email
                            $res = Mail::to($userAssignedTask->email)->send(new sendMail($task));
                        }
                        $check = NotificationSetting::where('user_id', $addedCollaborator)->where('webTask', 'true')->first();
                        // return $check;
                        if ($check) {
                            // Trigger the event
                            event(new NewNotificationEvent($userNotificationData));
                        }
                    }
                }
            }
        }

        return response()->json(['message' => 'Task updated successfully'], 200);
    }

    public function taskDelete($id)
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $task = TaskManagement::find($id);
        } else {
            $task = TaskManagement::where('id', $id)->Where('created_by', $user->id)->orWhere('collaboration', 'LIKE', '%' . $user->id . '%')->first();
        }

        if (!$task) {
            return response()->json(['message' => 'task not found'], 404);
        }

        $task->delete();

        return response()->json(['message' => 'task deleted successfully'], 200);
    }
}
