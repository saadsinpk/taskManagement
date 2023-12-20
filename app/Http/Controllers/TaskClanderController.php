<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskManagement;
use App\Models\TaskManagementStatus;
use App\Models\User;
use App\Models\OrderManagement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import the Validator facade
use Illuminate\Support\Carbon;

class TaskClanderController extends Controller
{
    public function calendarList()
    {
        $tasks = [];
        $user = Auth::user();
        if ($user->id == 1) {
            $tasks = TaskManagement::all();
        } else {
            $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->get();
        }

        $events = [];
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        foreach ($tasks as $task) {
            if ($task->status) {
                $find = TaskManagementStatus::find($task->status);
                if ($find) {
                    $data = TaskManagementStatus::select('background')->where('id', $task->status)->first();
                } else {
                    $data = [];
                }
            }
            $start_date = Carbon::parse($task->start_date);
            $deadline = Carbon::parse($task->deadline);

            // while ($start_date->lte($deadline)) {
            $eventMonth = $start_date->month;
            $eventYear = $start_date->year;

            // Check if the date is within the current, previous, or next month
            if ($eventYear == $currentYear && ($eventMonth == $currentMonth - 1 || $eventMonth == $currentMonth || $eventMonth == $currentMonth + 1)) {
                $events[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'color' => $data,
                    'start' => $start_date->format('Y-m-d'),
                    'end' => $task->deadline,
                ];
            }

            $start_date->addDay(); // Increment date by 1 day
            // }
        }

        // Sort the events by start date in ascending order
        usort($events, function ($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        return response()->json($events);
    }

    public function clanderViewTask($id)
    {
        $user = Auth::user();
        if ($user->id == 1) {
            $task = TaskManagement::where('id', $id)->first();
        } else {
            $task = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->first();
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

        return response()->json(['task' => $task], 200);
    }

    public function calendarListMonth(Request $request)
    {
        $tasks = [];
        $user = Auth::user();
        if ($user->id == 1) {
            $tasks = TaskManagement::all();
        } else {
            $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->get();
        }

        $events = [];
        $currentMonth = $request->month;
        $currentYear = $request->year;

        foreach ($tasks as $task) {
            if ($task->status) {
                $data = TaskManagementStatus::select('status_name')->where('id', $task->status)->first();
            }
            $start_date = Carbon::parse($task->start_date);
            $deadline = Carbon::parse($task->deadline);

            // while ($start_date->lte($deadline)) {
            $eventMonth = $start_date->month;
            $eventYear = $start_date->year;

            // Check if the date is within the current, previous, or next month or year
            if (
                ($eventYear == $currentYear && ($eventMonth == $currentMonth - 1 || $eventMonth == $currentMonth || $eventMonth == $currentMonth + 1))
                || ($currentMonth == 1 && $eventYear == $currentYear - 1 && $eventMonth == 12)
            ) {
                $events[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'color' => $data->background,
                    'start' => $start_date->format('Y-m-d'),
                    'end' => $task->deadline,
                ];
            }

            $start_date->addDay(); // Increment date by 1 day
            // }
        }

        // Sort the events by start date in ascending order
        usort($events, function ($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        return response()->json($events);
    }

    public function calendarListWeek(Request $request)
    {
        $tasks = [];
        $user = Auth::user();
        if ($user->id == 1) {
            $tasks = TaskManagement::all();
        } else {
            $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->get();
        }

        $events = [];
        $currentMonth = $request->month;
        $currentYear = $request->year;

        foreach ($tasks as $task) {
            if ($task->status) {
                $data = TaskManagementStatus::select('status_name')->where('id', $task->status)->first();
            }
            $start_date = Carbon::parse($task->start_date);
            $deadline = Carbon::parse($task->deadline);

            // while ($start_date->lte($deadline)) {
            $eventMonth = $start_date->month;
            $eventYear = $start_date->year;

            // Check if the date is within the current, previous, or next month or year
            if (
                ($eventYear == $currentYear && $eventMonth == $currentMonth)
            ) {
                $events[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'color' => $data->background,
                    'start' => $start_date->format('Y-m-d'),
                    'end' => $task->deadline,
                ];
            }

            $start_date->addDay(); // Increment date by 1 day
            // }
        }

        // Sort the events by start date in ascending order
        usort($events, function ($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        // Group events by week within the current month
        $eventsByWeek = [];
        $currentWeek = null;
        foreach ($events as $event) {
            $eventDate = Carbon::parse($event['start']);
            $weekNumber = $eventDate->weekOfMonth;

            if ($weekNumber !== $currentWeek) {
                $currentWeek = $weekNumber;
            }

            $eventsByWeek[] = $event;
        }

        return response()->json($eventsByWeek);
    }

    public function calendarListDay(Request $request)
    {
        $tasks = [];
        $user = Auth::user();
        if ($user->id == 1) {
            $tasks = TaskManagement::all();
        } else {
            $tasks = TaskManagement::where(function ($query) use ($user) {$query->where('assigned_to', $user->id)->orWhere('created_by', $user->id)->orWhereRaw("FIND_IN_SET(?, collaboration)", [$user->id]);})->get();
        }

        $events = [];
        $currentMonth = $request->month;
        $currentYear = $request->year;

        foreach ($tasks as $task) {
            if ($task->status) {
                $data = TaskManagementStatus::select('status_name')->where('id', $task->status)->first();
            }
            $start_date = Carbon::parse($task->start_date);
            $deadline = Carbon::parse($task->deadline);

            // while ($start_date->lte($deadline)) {
            $eventMonth = $start_date->month;
            $eventYear = $start_date->year;

            // Check if the date is within the current month and year
            if ($eventYear == $currentYear && $eventMonth == $currentMonth) {
                $events[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'color' => $data->background,
                    'start' => $start_date->format('Y-m-d'),
                    'end' => $task->deadline,
                ];
            }

            $start_date->addDay(); // Increment date by 1 day
            // }
        }

        // Sort the events by start date in ascending order
        usort($events, function ($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        // Group events by day within the current month
        $eventsByDay = [];
        foreach ($events as $event) {
            $eventDate = $event['start'];

            if (!isset($eventsByDay[$eventDate])) {
                $eventsByDay[$eventDate] = [];
            }

            $eventsByDay[] = $event;
        }

        return response()->json(array_values($eventsByDay));
    }
}
