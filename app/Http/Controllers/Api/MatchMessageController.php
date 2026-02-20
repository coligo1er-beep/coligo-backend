<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MatchMessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/matches/{id}/messages",
     *     operationId="getMatchMessages",
     *     tags={"Matches"},
     *     summary="Get match conversation history",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="messages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="match", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Match not found")
     * )
     */
    public function index(Request $request, $matchId)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 20), 100);

        // Verify user has access to this match
        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->with(['shipment', 'route', 'transporter', 'sender'])
            ->find($matchId);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        $messages = MatchMessage::where('match_id', $matchId)
            ->with(['sender'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Mark unread messages as read for current user
        MatchMessage::where('match_id', $matchId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Messages retrieved successfully',
            'data' => [
                'messages' => $messages->items(),
                'match' => $match,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'total_pages' => $messages->lastPage(),
                    'total_items' => $messages->total(),
                    'per_page' => $messages->perPage()
                ]
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/matches/{id}/messages",
     *     operationId="sendMatchMessage",
     *     tags={"Matches"},
     *     summary="Send message in match conversation",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Bonjour, je suis disponible pour ce transport."),
     *             @OA\Property(property="message_type", type="string", enum={"text", "system"}, example="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Match not found"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request, $matchId)
    {
        $user = $request->user();

        // Verify user has access to this match
        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->find($matchId);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        // Verify match is in a state where messaging is allowed
        if (!in_array($match->status, ['pending', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Messages cannot be sent for this match status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'message_type' => 'in:text,system'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $message = MatchMessage::create([
            'match_id' => $matchId,
            'sender_id' => $user->id,
            'message' => $request->message,
            'message_type' => $request->get('message_type', 'text')
        ]);

        $message->load(['sender', 'match']);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => [
                'message' => $message
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/matches/{id}/messages/{messageId}/read",
     *     operationId="markMessageRead",
     *     tags={"Matches"},
     *     summary="Mark message as read",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="messageId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Message not found")
     * )
     */
    public function markAsRead(Request $request, $matchId, $messageId)
    {
        $user = $request->user();

        // Verify user has access to this match
        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->find($matchId);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        // Find the message and verify it belongs to this match
        $message = MatchMessage::where('id', $messageId)
            ->where('match_id', $matchId)
            ->where('sender_id', '!=', $user->id) // Can't mark own message as read
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        // Mark as read if not already read
        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/matches/{id}/messages/mark-all-read",
     *     operationId="markAllMessagesRead",
     *     tags={"Matches"},
     *     summary="Mark all messages as read in match",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All messages marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="marked_count", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function markAllAsRead(Request $request, $matchId)
    {
        $user = $request->user();

        // Verify user has access to this match
        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->find($matchId);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        // Mark all unread messages from other user as read
        $markedCount = MatchMessage::where('match_id', $matchId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All messages marked as read',
            'data' => [
                'marked_count' => $markedCount
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/matches/{id}/messages/unread-count",
     *     operationId="getUnreadMessageCount",
     *     tags={"Matches"},
     *     summary="Get count of unread messages in match",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unread count retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="unread_count", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function unreadCount(Request $request, $matchId)
    {
        $user = $request->user();

        // Verify user has access to this match
        $match = MatchModel::where(function($query) use ($user) {
                $query->where('transporter_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->find($matchId);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        $unreadCount = MatchMessage::where('match_id', $matchId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Unread count retrieved successfully',
            'data' => [
                'unread_count' => $unreadCount
            ]
        ]);
    }
}