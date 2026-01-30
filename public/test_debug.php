<?php
// public/test_debug.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<h1>🔍 Deep Diagnostic Tool 🔍</h1>";

// 1. Database Check
try {
    $countStreams = \App\Models\LiveStream::count();
    $countChunks = \App\Models\AudioChunk::count();
    $lastChunk = \App\Models\AudioChunk::latest()->first();
    
    echo "<h2>📊 Database (MySQL)</h2>";
    echo "<b>Live Streams Count:</b> $countStreams<br>";
    echo "<b>Audio Chunks Count:</b> $countChunks<br>";
    
    if ($lastChunk) {
        echo "<b>Last Chunk ID:</b> " . $lastChunk->id . "<br>";
        echo "<b>Last Chunk Stream ID:</b> " . $lastChunk->live_stream_id . "<br>";
        echo "<b>Last Chunk Path:</b> " . $lastChunk->file_path . "<br>";
        echo "<b>Last Chunk Created:</b> " . $lastChunk->created_at . "<br>";
    } else {
        echo "⚠️ No chunks found in DB.<br>";
    }
} catch (\Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "<br>";
}

// 2. Storage Check
echo "<h2>📂 Storage Filesystem</h2>";
$basePath = storage_path('app/public/live_streams');
echo "<b>Scanning:</b> $basePath<br>";

if (is_dir($basePath)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath));
    $foundFiles = 0;
    echo "<ul>";
    foreach ($files as $file) {
        if ($file->isFile()) {
            echo "<li>" . $file->getFilename() . " (Size: " . $file->getSize() . ")</li>";
            $foundFiles++;
        }
    }
    echo "</ul>";
    if ($foundFiles == 0) echo "⚠️ Directory is empty.<br>";
} else {
    echo "❌ Directory does not exist!<br>";
}

// 3. Config Check
echo "<h2>⚙️ Configuration</h2>";
echo "<b>Storage Path:</b> " . storage_path() . "<br>";
echo "<b>Public Path:</b> " . public_path() . "<br>";
echo "<b>Asset URL:</b> " . asset('storage/test.txt') . "<br>";
