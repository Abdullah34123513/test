<?php
// public/debug_stream.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// List last 5 streams
$streams = \App\Models\LiveStream::orderBy('id', 'desc')->take(5)->get();

echo "<h1>Debug Streams</h1>";
foreach ($streams as $stream) {
    $chunkCount = \App\Models\AudioChunk::where('live_stream_id', $stream->id)->count();
    $lastChunk = \App\Models\AudioChunk::where('live_stream_id', $stream->id)->orderBy('id', 'desc')->first();
    
    echo "Stream ID: {$stream->id} | Status: {$stream->status} | User: {$stream->user_id} | Chunks: {$chunkCount} | ";
    if ($lastChunk) {
        $fullPath = public_path('storage/' . $lastChunk->file_path);
        echo "Last File: {$lastChunk->file_path} (Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . ")";
    } else {
        echo "No Chunks";
    }
    echo "<br>";
}
