<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\TaskManagement;
use App\Models\TaskManagementStatus;
use App\Models\OrderManagement;
use App\Models\OrderManagementStatus;
use App\Models\User;
use App\Models\ProjectOverview;
use App\Models\History;
use App\Models\ToDoListPrivate;
use App\Models\StickyNotes;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $currentWeekStart = Carbon::now()->startOfWeek();
        $currentWeekEnd = Carbon::now()->endOfWeek();
        $now = Carbon::now();

        if ($user->id == 1) {
            $task = TaskManagement::where('deadline', '<=', $now->endOfMonth())->get();

            $thisWeekTaskCreate = TaskManagement::whereBetween('start_date', [$currentWeekStart, $currentWeekEnd])->count();

            $thisWeekTaskDeadLine = TaskManagement::whereBetween('deadline', [$currentWeekStart, $currentWeekEnd])->count();

            $totalThisMonthTask = TaskManagement::where('deadline', '>=', $now)
                ->where('deadline', '<=', $now->endOfMonth())
                ->count();
        } else {
            $task = TaskManagement::where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id)
                      ->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);
            })->get();


            $thisWeekTaskCreate = TaskManagement::whereBetween('start_date', [$currentWeekStart, $currentWeekEnd])->where('assigned_to', $user->id)->count();

            $thisWeekTaskDeadLine = TaskManagement::whereBetween('deadline', [$currentWeekStart, $currentWeekEnd])->where('assigned_to', $user->id)->count();

            $totalThisMonthTask = TaskManagement::where('deadline', '>=', $now)
                ->where('deadline', '<=', $now->endOfMonth())
                ->where('assigned_to', $user->id)->count();
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
        }

        $prjOvew = ProjectOverview::all();
        $projectOverview = [];

        foreach ($prjOvew as $overview) {
            if ($user->id == 1) {
                $tskSttus = OrderManagementStatus::where('overview', $overview->id)->pluck('id');
                $taskss = OrderManagement::whereIn('id', $tskSttus)->count();
            } else {
                $tskSttus = OrderManagementStatus::where('overview', $overview->id)->pluck('id');
                $taskss = OrderManagement::where('created_by', $user->id)->whereIn('order_status', $tskSttus)->count();
            }
            // Organize the project overview data into an array
            $projectOverview[] = [
                'overview_id' => $overview->id,
                'overview_name' => $overview->name,
                'tasks_count' => $taskss,
            ];
        }

        $statuses = TaskManagementStatus::all();
        $AllTasksOverview = [];

        foreach ($statuses as $status) {
            if ($user->id == 1) {
                $tasks = TaskManagement::where('status', $status->id)->count();
            } else {
                $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->where('status', $status->id)->count();
            }

            $AllTasksOverview[] = [
                'status_id' => $status->id,
                'status_name' => $status->status_name,
                'status_color' => $status->background,
                'tasks' => $tasks,
            ];
        }

        $history = [];
        if ($user->id == 1) {
            $history = History::orderBy('created_at', 'desc')->get();
        } else {
            $history = History::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
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

        $todoList = ToDoListPrivate::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        $stickyNotes = StickyNotes::where('user_id', $user->id)->get();

        $startOfWeek = now()->startOfWeek(); // Assuming you want the week to start on Monday
        $endOfWeek = now()->endOfWeek();
        $notificationCount = Notification::where('user_id', $user->id)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->where('read_at', 0)->count();

        $cal1 = ProjectOverview::find(2);
        if ($user->id == 1) {
            $complete = TaskManagement::where('overview', $cal1->id)->count();
            $totalTask = TaskManagement::count();
        } else {
            $complete = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->where('overview', $cal1->id)->count();
            $totalTask = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->count();
        }

        $totalAns = 0;

        if ($totalTask > 0) {
            $multi = $complete * 100;
            $totalAns = $multi / $totalTask;
        }
        return response()->json([
            'thisWeekTaskCreate' => $thisWeekTaskCreate,
            'thisWeekTaskDeadLine' => $thisWeekTaskDeadLine,
            'totalThisMonthTask' => $totalThisMonthTask,
            'task' => $task,
            'projectOverview' => $projectOverview,
            'AllTasksOverview' => $AllTasksOverview,
            'history' => $history,
            'todoListPrivate' => $todoList,
            'stickyNotes' => $stickyNotes,
            'totalPercentage' => $totalAns,
            'notification' => $notificationCount,
        ]);
    }
}
