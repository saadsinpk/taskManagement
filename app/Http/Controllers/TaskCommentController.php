<?php

namespace App\Http\Controllers;

use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskCommentController extends Controller
{
    public function commentSend(Request $request)
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
        if (isset($input['image'])) {
            $uploadedFile = $request->file('image');
            $originalFilename = 'image';

            // Get the file extension
            $extension = $uploadedFile->getClientOriginalExtension();

            // Generate a unique filename based on the original filename, current date/time, and extension
            $currentDateTime = now()->format('Ymd-His');
            $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
            $input['image'] = $uploadedFile->storeAs('taskComment', $filename, 'public');
        }
        $taskComments = TaskComment::create($input);
        $taskComment = TaskComment::where('task_id', $request->task_id)->get();
        foreach ($taskComment as $key => $cmnt) {
            if ($cmnt->user_id) {
                $user = User::where('id', $cmnt->user_id)->first();
                if ($user) {
                    $taskComment[$key]['userInfo'] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'profile' => $user->profile
                    ];
                } else {
                    $taskComment[$key]['userInfo'] = [];
                }
            }
        }
        if ($taskComments) {
            return response()->json(['message' => 'Comment added successfully', 'taskComment' => $taskComment], 200);
        } else {
            return response()->json(['error' => 'Failed to add comment'], 500);
        }
    }

    public function commentEdit($id)
    {
        $user = Auth::user();
        $taskComments = TaskComment::find($id);
        if ($taskComments) {
            return response()->json(['taskComments' => $taskComments], 200);
        } else {
            return response()->json(['error' => "No Comment Found"], 404);
        }
    }

    public function commentUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $task = TaskComment::find($request->id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        if (isset($request->image)) {
            $uploadedFile = $request->file('image');
            $originalFilename = 'image';

            // Get the file extension
            $extension = $uploadedFile->getClientOriginalExtension();

            // Generate a unique filename based on the original filename, current date/time, and extension
            $currentDateTime = now()->format('Ymd-His');
            $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $currentDateTime . '.' . $extension;
            $image = $uploadedFile->storeAs('taskComment', $filename, 'public');
            $task->image = $image;
        }

        $task->comment = $request->comment;
        $task->save();
        // $task->update($request->all());
        $taskComment = TaskComment::where('task_id', $request->task_id)->get();
        foreach ($taskComment as $key => $cmnt) {
            if ($cmnt->user_id) {
                $user = User::where('id', $cmnt->user_id)->first();
                if ($user) {
                    $taskComment[$key]['userInfo'] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'profile' => $user->profile
                    ];
                } else {
                    $taskComment[$key]['userInfo'] = [];
                }
            }
        }
        return response()->json(['message' => 'Task comment updated successfully', 'taskComment' => $taskComment], 200);
    }

    public function commentDelete($id, $tasl_id)
    {
        $user = Auth::User();
        $taskComments = TaskComment::find($id);
        if (!$taskComments) {
            return response()->json(['message' => 'Task Comments not found'], 404);
        }

        $taskComments->delete();
        $taskComment = TaskComment::where('task_id', $tasl_id)->get();
        foreach ($taskComment as $key => $cmnt) {
            if ($cmnt->user_id) {
                $user = User::where('id', $cmnt->user_id)->first();
                if ($user) {
                    $taskComment[$key]['userInfo'] = [
                        'id' => $user->id,
                        'name' => $user->fname . ' ' . $user->lname,
                        'profile' => $user->profile
                    ];
                } else {
                    $taskComment[$key]['userInfo'] = [];
                }
            }
        }
        return response()->json(['message' => 'Task Comments deleted successfully', 'taskComment' => $taskComment], 200);
    }
}
