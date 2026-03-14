<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Shipment;
use App\Models\Route;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * List all conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('participant_1_id', $user->id)
            ->orWhere('participant_2_id', $user->id)
            ->with(['participant1', 'participant2', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        // Transform to include 'other_participant' and 'unread_count'
        $conversations->getCollection()->transform(function ($conv) use ($user) {
            $conv->other_participant = $conv->getOtherParticipant($user->id);
            $conv->unread_count = $conv->messages()
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
            return $conv;
        });

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Start or get a conversation from a source (shipment, route, match).
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:shipment,route,match',
            'source_id' => 'required|integer',
            'message' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $type = $request->type;
        $sourceId = $request->source_id;
        $recipientId = null;

        // Determine the recipient based on the source
        if ($type === 'shipment') {
            $source = Shipment::find($sourceId);
            if (!$source) return response()->json(['success' => false, 'message' => 'Shipment not found'], 404);
            $recipientId = $source->user_id;
        } elseif ($type === 'route') {
            $source = Route::find($sourceId);
            if (!$source) return response()->json(['success' => false, 'message' => 'Route not found'], 404);
            $recipientId = $source->user_id;
        } else {
            $source = MatchModel::find($sourceId);
            if (!$source) return response()->json(['success' => false, 'message' => 'Match not found'], 404);
            $recipientId = ($source->sender_id == $user->id) ? $source->transporter_id : $source->sender_id;
        }

        if ($recipientId == $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot start a conversation with yourself'], 400);
        }

        // Find or Create conversation
        $p1 = min($user->id, $recipientId);
        $p2 = max($user->id, $recipientId);

        $conversation = Conversation::firstOrCreate(
            [
                'type' => $type,
                'source_id' => $sourceId,
                'participant_1_id' => $p1,
                'participant_2_id' => $p2,
            ],
            ['last_message_at' => now()]
        );

        // If a message is provided, send it
        if ($request->filled('message')) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $request->message,
                'message_type' => 'text'
            ]);
            
            $conversation->update(['last_message_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation started',
            'data' => $conversation->load(['participant1', 'participant2', 'messages'])
        ]);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function messages(Request $request, $id)
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        // Check authorization
        if ($conversation->participant_1_id != $user->id && $conversation->participant_2_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = $request->user();

        // Check authorization
        if ($conversation->participant_1_id != $user->id && $conversation->participant_2_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:attachment|nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,mp3,wav,m4a,aac|max:5120', // 5MB
            'message_type' => 'required|in:text,image,audio,location'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $folder = $request->message_type == 'image' ? 'chat/photos' : 'chat/voice';
            $attachmentPath = $request->file('attachment')->store($folder, 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'message_type' => $request->message_type,
            'attachment_path' => $attachmentPath,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Broadcast event for real-time
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $message->load('sender')
        ]);
    }

    /**
     * Mark all messages in a conversation as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = $request->user();

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
    }
}
