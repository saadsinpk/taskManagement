<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import the Validator facade
use App\Models\TaskManagement;
use App\Models\TaskManagementStatus;
use App\Models\User;
use App\Models\OrderManagement;

class KanbanController extends Controller
{
    public function kanbanView()
    {
        $user = Auth::user();
        $statuses = TaskManagementStatus::all();

        $taskData = []; // Initialize an empty array to store the task data

        foreach ($statuses as $status) {
            if ($user->id == 1) {
                $tasks = TaskManagement::where('status', $status->id)->get();
            } else {
                $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->where('status', $status->id)->get();
            }
            foreach ($tasks as $key_task => $tsk) {
                if ($tsk->status) {
                    $data = TaskManagementStatus::select('status_name')->where('id', $tsk->status)->first();
                    if ($data !== null) {
                        $tsk['statusName'] = $data->status_name;
                    }
                }
                // if ($tsk->assigned_to) {
                //     $users = User::select('fname')->where('id', $tsk->assigned_to)->first();
                //     if ($users) {
                //         $tsk['assigned_to_name'] = $users->fname;
                //     } else {
                //         $tsk['assigned_to_name'] = 'SuperAdmin';
                //     }
                // }
                if ($tsk->order_id) {
                    $orderDta = OrderManagement::select('order_name')->where('id', $tsk->order_id)->first();
                    if ($orderDta) {
                        $tsk['order_name'] = $orderDta->order_name;
                    } else {
                        $tsk['order_name'] = [];
                    }
                }
                if ($tsk->collaboration) {
                    $userDetails = [];
                    $collaborationIds = explode(',', $tsk->collaboration);
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
                    $tasks[$key_task]['userDetails'] = $userDetails;
                }
            }
            $taskData[] = [
                'status_id' => $status->id,
                'status_name' => $status->status_name,
                'status_color' => $status->background,
                'total_task' => $tasks->count(),
                'tasks' => $tasks,
            ];
        }
        return $taskData;
    }

    public function kanbanUpdate(Request $request)
    {
        $user = Auth::user();
        $task = TaskManagement::where('id', $request->id)->first();

        if ($task != null) {
            $task->update([
                'status' => $request->status
            ]);
            $user = Auth::user();
            $statuses = TaskManagementStatus::all();

            $taskData = []; // Initialize an empty array to store the task data

            foreach ($statuses as $status) {
                if ($user->id == 1) {
                    $tasks = TaskManagement::where('status', $status->id)->get();
                } else {
                    $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->where('status', $status->id)->get();
                }

                $taskData[] = [
                    'status_id' => $status->id,
                    'status_name' => $status->status_name,
                    'status_color' => $status->background,
                    'total_task' => $tasks->count(),
                    'tasks' => $tasks,
                ];
            }
            return $taskData;
        } else {
            return response()->json(['error' => 'Task not found. Please try again later!'], 503);
        }
    }
}
