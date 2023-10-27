<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectOverview;
use App\Models\TaskManagementStatus;
use App\Models\OrderManagementStatus;

class ProjectOverviewController extends Controller
{
    public function projectOverviewList()
    {
        $projectOverviews = ProjectOverview::all();

        return response()->json($projectOverviews);
    }

    public function projectOverviewUpdate(Request $request)
    {
        if ($request->type == 'task') {
            $title = 'Task';
            $data = TaskManagementStatus::find($request->id);
        } elseif ($request->type == 'order') {
            $title = 'Order';
            $data = OrderManagementStatus::find($request->id);
        } else {
            return response()->json(['error' => 'Something went wrong. Please try again later!'], 503);
        }

        $data->update([
            'overview' => $request->prj_id,
        ]);

        return response()->json(['message' => $title . ' updated successfully'], 200);
    }
}
