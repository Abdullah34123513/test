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
     * Get list of users with last message (optional, for chat list).
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // elaborate query to get recent chat users
        // For simplicity, just returning all users for now or specific logic
        // In a real WhatsApp clone, you'd group messages by user.
        
        return response()->json(['message' => 'Chat list endpoint']);
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
        
        // This is a placeholder for your existing Firebase logic.
        // You mentioned "use firebase for notification".
        // Assuming you have a mechanism to send FCM messages.
        
        try {
            // Example payload
            $data = [
                'title' => 'New Message',
                'body' => $message->type === 'text' ? $message->message : 'Sent a ' . $message->type,
                'sender_id' => $message->sender_id,
                'type' => 'chat_message',
            ];
            
            // Call your notification service here
            // Notification::send($receiver, new NewMessageNotification($message));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('FCM Error: ' . $e->getMessage());
        }
    }
}
