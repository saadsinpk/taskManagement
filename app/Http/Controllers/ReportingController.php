<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\TaskManagement;
use App\Models\TaskManagementStatus;
use App\Models\User;
use App\Models\OrderManagement;

class ReportingController extends Controller
{
    public function reportYear()
    {
        $user = Auth::user();
        $currentYear = Carbon::now()->year;

        $monthlyTaskCounts = [];
        $statuses = TaskManagementStatus::all();

        for ($month = 1; $month <= 12; $month++) {
            $monthlyCounts = [];
            foreach ($statuses as $status) {
                if ($user->id == 1) {
                    $tasksInMonth = TaskManagement::whereYear('start_date', $currentYear)
                        ->whereMonth('start_date', $month)
                        ->where('status', $status->id)
                        ->count();
                } else {
                    $tasksInMonth = TaskManagement::whereYear('start_date', $currentYear)
                        ->whereMonth('start_date', $month)
                        ->where('assigned_to', $user->id)
                        ->where('status', $status->id)
                        ->count();
                }
                // $monthlyCounts[] = [
                //     $status->status_name => $tasksInMonth,
                //     'background' => $status->background,
                // ];
                // $monthlyCounts[$status->status_name] = $tasksInMonth;
                $monthlyCounts[] = [
                    'status_name' => $status->status_name,
                    'background' => $status->background,
                    'count' => $tasksInMonth,
                ];
            }

            $monthlyTaskCounts[Carbon::createFromDate($currentYear, $month, 1)->format('F')] = $monthlyCounts;
        }

        return response()->json([
            'monthlyTaskCounts' => $monthlyTaskCounts,
        ]);
    }

    public function reportMonth()
    {
        $user = Auth::user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $daysInCurrentMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->daysInMonth;

        $dailyTaskCounts = [];
        $statuses = TaskManagementStatus::all();

        for ($day = 1; $day <= $daysInCurrentMonth; $day++) {
            $dailyCounts = [];
            foreach ($statuses as $status) {
                if ($user->id == 1) {
                    $tasksInDay = TaskManagement::whereYear('start_date', $currentYear)
                        ->whereMonth('start_date', $currentMonth)
                        ->whereDay('start_date', $day)
                        ->where('status', $status->id)
                        ->count();
                } else {
                    $tasksInDay = TaskManagement::whereYear('start_date', $currentYear)
                        ->whereMonth('start_date', $currentMonth)
                        ->whereDay('start_date', $day)
                        ->where('assigned_to', $user->id)
                        ->where('status', $status->id)
                        ->count();
                }

                $dailyCounts[] = [
                    'status_name' => $status->status_name,
                    'background' => $status->background,
                    'tasksInDay' => $tasksInDay,
                ];
            }
            // Store the task count in the dailyTaskCounts array
            $dailyTaskCounts[$day] = $dailyCounts;
        }

        return response()->json([
            'dailyTaskCounts' => $dailyTaskCounts,
        ]);
    }

    public function reportWeek()
    {
        $user = Auth::user();
        $currentWeek = Carbon::now()->week;
        $currentYear = Carbon::now()->year;

        // Initialize an array to store task counts for each day of the week
        $dailyTaskCounts = [];
        $statuses = TaskManagementStatus::all();

        for ($day = 1; $day <= 7; $day++) {
            $weeklyCounts = [];
            foreach ($statuses as $status) {
                $date = Carbon::now()->setISODate($currentYear, $currentWeek, $day);

                // Get the tasks for each day
                if ($user->id == 1) {
                    $tasksInDay = TaskManagement::whereYear('start_date', $date->year)
                        ->whereMonth('start_date', $date->month)
                        ->whereDay('start_date', $date->day)
                        ->where('status', $status->id)
                        ->count();
                } else {
                    $tasksInDay = TaskManagement::whereYear('start_date', $date->year)
                        ->whereMonth('start_date', $date->month)
                        ->whereDay('start_date', $date->day)
                        ->where('assigned_to', $user->id)
                        ->where('status', $status->id)
                        ->count();
                }

                $weeklyCounts[] = [
                    'status_name' => $status->status_name,
                    'background' => $status->background,
                    'weeklycount' => $tasksInDay,
                ];
            }
            // Store the task count in the dailyTaskCounts array
            $dailyTaskCounts[$date->toDateString()] = $weeklyCounts;
        }

        return response()->json([
            'dailyTaskCounts' => $dailyTaskCounts,
        ]);
    }

    public function reportDay()
    {
        $user = Auth::user();
        $currentDay = Carbon::now()->day;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $hourlyTaskCounts = [];
        $statuses = TaskManagementStatus::all();

        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyCounts = [];

            foreach ($statuses as $status) {
                $startHour = Carbon::create($currentYear, $currentMonth, $currentDay, $hour, 0, 0);
                $endHour = Carbon::create($currentYear, $currentMonth, $currentDay, $hour, 59, 59);

                $tasksInHour = TaskManagement::whereBetween('start_date', [$startHour, $endHour]);

                if ($user->id != 1) {
                    $tasksInHour->where('assigned_to', $user->id);
                }

                $tasksInHour = $tasksInHour->where('status', $status->id)->count();

                $hourlyCounts[] = [
                    'status_name' => $status->status_name,
                    'background' => $status->background,
                    'hourCount' => $tasksInHour,
                ];
            }

            $hourlyTaskCounts[$hour] = $hourlyCounts;
        }

        return response()->json([
            'hourlyTaskCounts' => $hourlyTaskCounts,
        ]);
    }

    public function fetchDataBetweenDates(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($user->id == 1) {
            $tasksInDateRange = TaskManagement::whereBetween('start_date', [$start, $end])->get();
        } else {
            $tasksInDateRange = TaskManagement::whereBetween('start_date', [$start, $end])
                ->where('assigned_to', $user->id)
                ->get();
        }

        foreach ($tasksInDateRange as $task) {
            if ($task->status) {
                $data = TaskManagementStatus::select('status_name')->where('id', $task->status)->first();
                if ($data !== null) {
                    $task['statusName'] = $data->status_name;
                }
            }
            if ($task->assigned_to) {
                $users = User::select('fname')->where('id', $task->assigned_to)->first();
                if ($users) {
                    $task['assigned_to_name'] = $users->fname;
                } else {
                    $task['assigned_to_name'] = 'SuperAdmin';
                }
            }
            if ($task->order_id) {
                $orderData = OrderManagement::select('order_name')->where('id', $task->order_id)->first();
                if ($orderData) {
                    $task['order_name'] = $orderData->order_name;
                } else {
                    $task['order_name'] = [];
                }
            }
        }

        return response()->json([
            'tasksInDateRange' => $tasksInDateRange,
            'taskCount' => $tasksInDateRange->count()
        ]);
    }
}
