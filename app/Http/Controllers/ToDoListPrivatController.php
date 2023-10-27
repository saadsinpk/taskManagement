<?php

namespace App\Http\Controllers;

use App\Models\ToDoListPrivate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ToDoListPrivatController extends Controller
{
    public function todoCreate(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['user_id'] = $user->id;
        $taskmngstatus = ToDoListPrivate::create($input);
        if ($taskmngstatus) {
            $todoList = ToDoListPrivate::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->get();
            return response()->json(['message' => 'ToDo created successfully', 'todoList' => $todoList], 200);
            // return response()->json(['message' => 'ToDo created successfully'], 201);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }
    }

    public function todoList()
    {
        $user = Auth::user();

        $todoList = ToDoListPrivate::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['todoList' => $todoList], 200);
    }

    public function todoEdit($id)
    {
        $user = Auth::user();

        $todoList = ToDoListPrivate::where('user_id', $user->id)
            ->where('id', $id)
            ->get();

        return response()->json(['todoList' => $todoList], 200);
    }

    public function todoUpdate(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'id' => 'required',
        ]);

        $todo = ToDoListPrivate::find($request->id);

        if (!$todo) {
            return response()->json(['message' => 'Todo not found'], 404);
        }

        $todo->update($request->all());

        $todoList = ToDoListPrivate::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();
        return response()->json(['message' => 'ToDo updated successfully', 'todoList' => $todoList], 200);
        // return response()->json(['message' => 'ToDo updated successfully'], 200);
    }

    public function todoDelete(Request $request)
    {
        $user = Auth::user();
        $id = $request->id;
        $todo = ToDoListPrivate::where('id', $id)->where('user_id', $user->id)->first();

        if (!$todo) {
            return response()->json(['message' => 'todo not found'], 404);
        }

        $todo->delete();

        return response()->json(['message' => 'todo deleted successfully'], 200);
    }
}
