<?php
// public/test_debug.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// 1. Test Logging
try {
    Illuminate\Support\Facades\Log::info('WEB_DEBUG_TEST: Logging works from Browser!');
    echo "✅ Logging triggered (check logs).<br>";
} catch (\Exception $e) {
    echo "❌ Logging Failed: " . $e->getMessage() . "<br>";
}

// 2. Check Upload Limit
echo "Upload Limit: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Limit: " . ini_get('post_max_size') . "<br>";

// 3. Check Storage Write
try {
    Illuminate\Support\Facades\Storage::disk('public')->put('web_test.txt', 'Hello from Web');
    echo "✅ Storage Write Success: " . Illuminate\Support\Facades\Storage::disk('public')->path('web_test.txt') . "<br>";
} catch (\Exception $e) {
    echo "❌ Storage Write Failed: " . $e->getMessage() . "<br>";
}

// 4. Check folder being uploaded to
$targetDir = storage_path('app/public/live_streams');
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "Created missing dir: $targetDir<br>";
} else {
    echo "Target dir exists: $targetDir<br>";
}
