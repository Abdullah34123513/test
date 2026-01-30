<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\AudioChunk;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    public function start(Request $request)
    {
        $stream = LiveStream::create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Live stream started',
            'live_stream_id' => $stream->id,
        ]);
    }

    public function uploadChunk(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('UploadChunk: RAW REQUEST HIT', [
            'data' => $request->all(),
            'files' => $request->allFiles(),
            'content_type' => $request->header('Content-Type')
        ]);

        try {
            $request->validate([
                'live_stream_id' => 'required|exists:live_streams,id',
                'file' => 'required|file', // Removed strict mime check for debugging
                'sequence_number' => 'required|integer',
                'duration' => 'nullable|numeric',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('UploadChunk Validation Failed', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($request->hasFile('file')) {
            \Illuminate\Support\Facades\Log::info('UploadChunk: File found', ['id' => $request->live_stream_id]);
            
            // Force .m4a extension for browser compatibility
            $filename = 'chunk_' . $request->sequence_number . '_' . time() . '.m4a';
            $path = $request->file('file')->storeAs('live_streams/' . $request->live_stream_id, $filename, 'public');

            $chunk = AudioChunk::create([
                'live_stream_id' => $request->live_stream_id,
                'file_path' => $path,
                'sequence_number' => $request->sequence_number,
                'duration' => $request->duration,
            ]);

            return response()->json([
                'message' => 'Chunk uploaded successfully',
                'chunk_id' => $chunk->id,
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    public function end(Request $request)
    {
        $request->validate([
            'live_stream_id' => 'required|exists:live_streams,id',
        ]);

        $stream = LiveStream::find($request->live_stream_id);
        
        if (!$stream) {
            return response()->json(['message' => 'Live stream not found'], 404);
        }

        // Note: The original code included a user_id check here.
        // The instruction removes it, so we proceed without it.

        $stream->status = 'ended';
        $stream->ended_at = now();
        $stream->save();
        
        // Auto-Process Recording (Node.js)
        $script = base_path('merge_processor.cjs');
        $nodePath = '/home/u896481526/node-v22.18.0-linux-x64/bin/node';
        $command = "$nodePath " . escapeshellarg($script) . " " . escapeshellarg($stream->id) . " > /dev/null 2>&1 &";
        exec($command);

        return response()->json([
            'status' => 'stream_ended'
        ]);
    }
}
