<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OrderManagement;
use App\Models\OrderManagementStatus;
use App\Models\TaskManagement;
use App\Models\TaskManagementStatus;
use App\Models\NotificationSetting;
use App\Events\NewNotificationEvent;
use App\Models\ProjectOverview;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import the Validator facade

class OrderManagementController extends Controller
{
    public function orderCreate(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'order_name' => 'required',
            'order_status' => 'required',
            'date_created' => 'required',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['created_by'] = $user->id;
        $ordermng = OrderManagement::create($input);
        if ($ordermng) {
            // Get arrays of values from the form data
            $titles = $input['title'];
            $assignedTo = $input['assigned_to'];
            $priorities = $input['priority'];
            $statuses = $input['status'];
            $startDates = $input['start_date'];
            $deadlines = $input['deadline'];
            $descriptions = $input['description'];

            // Iterate through the arrays and create tasks for each set of data
            foreach ($titles as $key => $title) {
                $tttsk = TaskManagement::create([
                    'order_id' => $ordermng->id,
                    'title' => $title,
                    'assigned_to' => $assignedTo[$key],
                    'priority' => $priorities[$key],
                    'status' => $statuses[$key],
                    'start_date' => $startDates[$key],
                    'deadline' => $deadlines[$key],
                    'description' => $descriptions[$key],
                    'created_by' => $user->id,
                    // Other task fields here
                ]);
                Notification::create([
                    'user_id' => $assignedTo[$key],
                    'type' => 'TaskAssigned',
                    'notifiable_id' => $tttsk->id,
                    'notifiable_type' => TaskManagement::class,
                    'data' =>  $ordermng->id,
                    'read_at' => '0',
                ]);

                $received = intval($assignedTo[$key]);
                $count = Notification::where('user_id', $received)->where('read_at', 0)->count();
                $userNotificationData = [
                    'id' => $received,
                    'count' => $count,
                ];

                // Trigger the event
                event(new NewNotificationEvent($userNotificationData));
            }
            return response()->json(['message' => 'Order created successfully'], 201);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function orderListDropdown()
    {
        $user = Auth::user();

        $order = OrderManagement::all();

        foreach ($order as $odr) {
            if ($odr->order_status) {
                $data = OrderManagementStatus::select('status_name')->where('id', $odr->order_status)->first();
                if ($data !== null) {
                    $odr['statusName'] = $data->status_name;
                }
            }
            if ($odr->id) {
                $count = Taskmanagement::where('order_id', $odr->id)->count();
                $odr['taskUser'] = $count;
            }
        }
        return response()->json(['order' => $order], 200);
    }

    public function orderList()
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $order = OrderManagement::all();
        } else {
            $userId = $user->id;
            $taskOrderIds = TaskManagement::where('assigned_to', $userId)
                ->pluck('order_id');

            $order = OrderManagement::whereIn('id', $taskOrderIds)->orWhere('created_by', $userId)
                ->get();
        }
        foreach ($order as $odr) {
            if ($odr->order_status) {
                $data = OrderManagementStatus::select('status_name')->where('id', $odr->order_status)->first();
                if ($data !== null) {
                    $odr['statusName'] = $data->status_name;
                }
            }
            if ($odr->id) {
                $count = Taskmanagement::where('order_id', $odr->id)->count();
                $odr['taskUser'] = $count;
            }
            if ($odr->overview) {
                $orderDta = ProjectOverview::select('name')->where('id', $odr->overview)->first();
                if ($orderDta) {
                    $odr['overview_name'] = $orderDta->name;
                } else {
                    $odr['overview_name'] = [];
                }
            }
        }
        return response()->json(['order' => $order], 200);
    }

    public function orderEdit($id)
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $order = OrderManagement::where('id', $id)->first();
        } else {
            $order = OrderManagement::where('id', $id)->where('created_by', $user->id)->first();
        }

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->order_status) {
            $data = OrderManagementStatus::select('status_name')->where('id', $order->order_status)->first();
            if ($data !== null) {
                $order['statusName'] = $data->status_name;
            }
        }
        if ($order->id) {
            $order['taskUser'] = TaskManagement::where('order_id', $order->id)->get();
            foreach ($order['taskUser'] as $tsk) {
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
            // if ($data !== null) {
            //     $order['taskUser'] = $data;
            // }
        }

        $status = OrderManagementStatus::all();

        return response()->json(['order' => $order, 'status' => $status], 200);
    }

    public function orderUpdate(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'order_id' => 'required|exists:order_management,id',
        ]);
        $order = OrderManagement::find($request->order_id); // Use 'find()' to retrieve a single record by primary key

        if (!$order) {
            return response()->json(['message' => 'order not found'], 404);
        }

        $input = $request->all();
        $ordermng = $order->update($input);
        if ($ordermng) {
            foreach ($request->task_id as $key => $taskId) {
                if ($taskId == 0) {
                    $newTask = TaskManagement::create([
                        'order_id' => $order->id,
                        'title' => $request->title[$key],
                        'assigned_to' => $request->assigned_to[$key],
                        'priority' => $request->priority[$key],
                        'status' => $request->status[$key],
                        'start_date' => $request->start_date[$key],
                        'deadline' => $request->deadline[$key],
                        'description' => $request->description[$key],
                        'created_by' => $user->id,
                        // Other task fields here
                    ]);
                    Notification::create([
                        'user_id' => $request->assigned_to[$key],
                        'type' => 'TaskAssigned',
                        'notifiable_id' => $newTask->id,
                        'notifiable_type' => TaskManagement::class,
                        'data' =>  $order->id,
                        'read_at' => '0',
                    ]);
                    $received = intval($request->assigned_to[$key]);
                    $count = Notification::where('user_id', $received)->where('read_at', 0)->count();
                    $userNotificationData = [
                        'id' => $received,
                        'count' => $count,
                    ];

                    // Trigger the event
                    event(new NewNotificationEvent($userNotificationData));
                } else {
                    $task = TaskManagement::find($taskId);

                    if (!$task) {
                        return response()->json(['message' => 'Task not found for ID: ' . $taskId], 404);
                    }

                    $task->update([
                        'title' => $request->title[$key],
                        'assigned_to' => $request->assigned_to[$key],
                        'priority' => $request->priority[$key],
                        'status' => $request->status[$key],
                        'start_date' => $request->start_date[$key],
                        'deadline' => $request->deadline[$key],
                        'description' => $request->description[$key],
                        // Other task fields here
                    ]);
                }
            }
            return response()->json(['message' => 'order updated successfully'], 200);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function orderDelete($id)
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $order = OrderManagement::find($id);
        } else {
            $order = OrderManagement::where('id', $id)->where('created_by', $user->id)->first();
        }

        if (!$order) {
            return response()->json(['message' => 'order not found'], 404);
        }

        $order->delete();
        TaskManagement::where('order_id', $id)->delete();

        return response()->json(['message' => 'order deleted successfully'], 200);
    }
}
