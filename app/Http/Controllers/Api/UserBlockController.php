<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserBlockController extends Controller
{
    /**
     * List all users blocked by the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $blocks = UserBlock::where('blocker_id', $user->id)
            ->with('blocked')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $blocks
        ]);
    }

    /**
     * Block a user.
     */
    public function block(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blocked_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $blockedId = $request->blocked_id;

        if ($user->id == $blockedId) {
            return response()->json(['success' => false, 'message' => 'You cannot block yourself'], 400);
        }

        $block = UserBlock::firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_id' => $blockedId
        ], [
            'reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
            'data' => $block
        ]);
    }

    /**
     * Unblock a user.
     */
    public function unblock(Request $request, $id)
    {
        $user = $request->user();
        
        $deleted = UserBlock::where('blocker_id', $user->id)
            ->where('blocked_id', $id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'User unblocked successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Block not found'
        ], 404);
    }
}
