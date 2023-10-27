<?php

namespace App\Http\Controllers;

use App\Models\StickyNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StickyNotesController extends Controller
{
    public function stickyNotesUpdate(Request $request)
    {
        $user = Auth::user();

        $stickyNote = StickyNotes::where('user_id', $user->id)->first();

        if ($stickyNote) {
            // $request->validate([
            //     'id' => 'required',
            // ]);

            $stickyNote->update($request->all());
        } else {
            $stickyNote = StickyNotes::create([
                'user_id' => $user->id,
                'notes' => $request->notes,
                'color' => $request->color,
            ]);
        }

        return response()->json(['message' => 'Sticky note updated or created', 'stickyNote' => $stickyNote], 200);
    }
}
