<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommandLog;

class CommandLogController extends Controller
{
    public function updateStatus(Request $request)
    {
        $request->validate([
            'command_id' => 'required|exists:command_logs,id',
            'status' => 'required|in:delivered,executed,failed',
            'response_message' => 'nullable|string',
        ]);

        $log = CommandLog::find($request->command_id);
        
        // Prevent rewinding status (e.g. executed -> delivered)
        if ($log->status === 'executed' && $request->status !== 'executed') {
             return response()->json(['message' => 'Already executed']);
        }

        $log->status = $request->status;
        if ($request->has('response_message')) {
            $log->response_message = $request->response_message;
        }

        if ($request->status === 'delivered') {
            $log->delivered_at = now();

            if ($log->admin_id) {
                $recipient = \App\Models\User::find($log->admin_id);
                if ($recipient) {
                    \Filament\Notifications\Notification::make()
                        ->title('Command Delivered')
                        ->body("Mobile device received command: {$log->command}")
                        ->success()
                        ->sendToDatabase($recipient);
                }
            }
        } elseif ($request->status === 'executed') {
            $log->executed_at = now();
        }

        $log->save();

        return response()->json(['message' => 'Status updated']);
    }
}
