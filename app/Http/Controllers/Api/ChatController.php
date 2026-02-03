<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
// use App\Services\FirebaseService; // Assuming you might have a service for notifications

class ChatController extends Controller
{
    /**
     * Get list of all users with last message info for chat list.
     */
    public function getUsers(Request $request)
    {
        $currentUserId = $request->user()->id;

        // Get all users except current user
        $users = User::where('id', '!=', $currentUserId)->get();

        $result = [];
        foreach ($users as $user) {
            // Get last message between current user and this user
            $lastMessage = Message::where(function ($q) use ($currentUserId, $user) {
                $q->where('sender_id', $currentUserId)
                  ->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($currentUserId, $user) {
                $q->where('sender_id', $user->id)
                  ->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'desc')
            ->first();

            // Get unread count
            $unreadCount = Message::where('sender_id', $user->id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->count();

            $result[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'last_message' => $lastMessage ? ($lastMessage->type === 'text' ? $lastMessage->message : 'Sent a ' . $lastMessage->type) : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at->format('g:i A') : null,
                'last_message_at' => $lastMessage ? $lastMessage->created_at->toISOString() : null,
                'unread_count' => $unreadCount,
            ];
        }

        // Sort by last message time (most recent first)
        usort($result, function ($a, $b) {
            if ($a['last_message_at'] === null && $b['last_message_at'] === null) return 0;
            if ($a['last_message_at'] === null) return 1;
            if ($b['last_message_at'] === null) return -1;
            return strcmp($b['last_message_at'], $a['last_message_at']);
        });

        return response()->json($result);
    }

    /**
     * Get messages between current user and another user.
     */
    public function getMessages(Request $request, $userId)
    {
        $authUserId = $request->user()->id;

        $messages = Message::where(function ($q) use ($authUserId, $userId) {
            $q->where('sender_id', $authUserId)
              ->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($authUserId, $userId) {
            $q->where('sender_id', $userId)
              ->where('receiver_id', $authUserId);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // Mark as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', $authUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Send a message (Text, Image, Audio).
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|in:text,image,audio',
            'message' => 'nullable|string',
            'file' => 'required_if:type,image,audio|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $senderId = $request->user()->id;
        $filePath = null;

        if ($request->hasFile('file')) {
            $directory = 'chat_files/' . $request->type . 's';
            $filePath = $request->file('file')->store($directory, 'public');
        }

        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message, // Can be caption for images/audio
            'type' => $request->type,
            'file_path' => $filePath,
        ]);

        // TODO: Send Firebase Notification here
        $this->sendNotification($message);

        return response()->json($message, 201);
    }

    private function sendNotification($message)
    {
        $receiver = User::find($message->receiver_id);
        
        if (!$receiver || !$receiver->fcm_token) {
            return;
        }

        try {
            $firebaseService = new \App\Services\FirebaseService();
            
            $title = $message->sender ? $message->sender->name : 'New Message';
            $body = $message->type === 'text' ? $message->message : 'Sent a ' . $message->type;
            
            $data = [
                'type' => 'chat_message',
                'sender_id' => (string) $message->sender_id,
                'sender_name' => $message->sender ? $message->sender->name : '',
                'message_id' => (string) $message->id,
                'chat_type' => $message->type,
            ];

            $firebaseService->sendToToken($receiver->fcm_token, $title, $body, $data);
            
        } catch (\Exception $e) {
            \Log::error('FCM Error: ' . $e->getMessage());
        }
    }
}
