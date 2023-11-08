<?php

namespace App\Http\Controllers;

use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskActivityController extends Controller
{
    public function activityCheckIn(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['user_id'] = $user->id;
        $taskNewActivity = TaskActivity::create($input);
        

        if ($taskNewActivity) {
            $user = User::find($user->id);

            if ($user) {
                $userInfo = [
                    'id' => $user->id,
                    'name' => $user->fname . ' ' . $user->lname,
                    'profile' => $user->profile
                ];
            } else {
                $userInfo = [];
            }

            $taskActivity = TaskActivity::where('task_id', $request->task_id)->orderBy('id', 'Desc')->get();
           
            foreach ($taskActivity as $taskActivities) {
                $checkInTime = Carbon::parse($taskActivities->checkIn);
                $taskActivities->checkIn  = $checkInTime->format('Y-m-d h:i A');
    
                $totalTime = 0;
                if ($taskActivities->checkOut) {
                    $checkInTime = Carbon::parse($taskActivities->checkIn);
                    $checkOutTime = Carbon::parse($taskActivities->checkOut);
                    $totalTime += $checkOutTime->diffInMinutes($checkInTime);
    
                    $checkInTime = Carbon::parse($taskActivities->checkOut);
                    $taskActivities->checkOut  = $checkInTime->format('Y-m-d h:i A');
    
                }
    
                $totalHours = floor($totalTime / 60);
                $totalMinutes = $totalTime % 60;
                $taskActivities->total_hours = $totalHours . ' hr : ' . $totalMinutes . ' min';
            }
            
            foreach ($taskActivity as $key => $cmnt) {
                if ($cmnt->user_id) {
                    $user = User::where('id', $cmnt->user_id)->first();
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
            return response()->json(['message' => 'CheckIn successfully', 'taskActivity' => $taskActivity, 'taskNewActivity' => $taskNewActivity], 200);
        } else {
            return response()->json(['error' => 'Failed to add activity'], 500);
        }
    }

    public function activityCheckOut(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'task_id' => 'required',
        ]);

        $activity = TaskActivity::where('id', $request->id)->where('task_id', $request->task_id)->first();

        if (!$activity) {
            return response()->json(['message' => 'activity not found'], 404);
        }

        if ($activity->update($request->all())) {
            $taskActivity = TaskActivity::where('task_id', $request->task_id)->orderBy('id', 'Desc')->get();
           
            foreach ($taskActivity as $taskActivities) {
                $checkInTime = Carbon::parse($taskActivities->checkIn);
                $taskActivities->checkIn  = $checkInTime->format('Y-m-d h:i A');
    
                $totalTime = 0;
                if ($taskActivities->checkOut) {
                    $checkInTime = Carbon::parse($taskActivities->checkIn);
                    $checkOutTime = Carbon::parse($taskActivities->checkOut);
                    $totalTime += $checkOutTime->diffInMinutes($checkInTime);
    
                    $checkInTime = Carbon::parse($taskActivities->checkOut);
                    $taskActivities->checkOut  = $checkInTime->format('Y-m-d h:i A');
    
                }
    
                $totalHours = floor($totalTime / 60);
                $totalMinutes = $totalTime % 60;
                $taskActivities->total_hours = $totalHours . ' hr : ' . $totalMinutes . ' min';
            }
            
            foreach ($taskActivity as $key => $cmnt) {
                if ($cmnt->user_id) {
                    $user = User::where('id', $cmnt->user_id)->first();
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
            return response()->json(['message' => 'CheckOut successfully', 'taskActivity' => $taskActivity], 200);
        } else {
            return response()->json(['error' => 'CheckOut failed'], 500);
        }
    }
}
