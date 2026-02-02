<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-db', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "<h1>Database Diagnostics</h1>";
        echo "✅ Connection OK<br>";
        echo "📂 Database: " . \Illuminate\Support\Facades\DB::connection()->getDatabaseName() . "<br>";
        
        $hasTable = \Illuminate\Support\Facades\Schema::hasTable('device_logs');
        echo "📋 Table 'device_logs': " . ($hasTable ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . "<br>";
        
        $migration = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%create_device_logs_table%')->first();
        echo "📦 Migration Record: " . ($migration ? "Found (Batch {$migration->batch})" : '<span style="color:red">NOT FOUND</span>') . "<br>";
        
        echo "<h2>Model Check</h2>";
        $file = base_path('app/Models/DeviceLog.php');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            echo "📂 File Content (First 10 lines):<pre>" . htmlspecialchars(substr($content, 0, 300)) . "</pre>";
        } else {
            echo "❌ File Missing: $file<br>";
        }

        try {
            if (!class_exists(\App\Models\DeviceLog::class)) {
                throw new \Exception("Class \App\Models\DeviceLog not found!");
            }
            $count = \App\Models\DeviceLog::count();
            echo "📊 DeviceLog Count: $count<br>";
        } catch (\Throwable $e) {
            echo "❌ DeviceLog Model Error: " . $e->getMessage() . "<br>";
        }

        echo "<h2>Media Check (Last 5 Uploads)</h2>";
        try {
            $media = \App\Models\Media::latest()->take(5)->get();
            if ($media->isEmpty()) {
                echo "⚠️ No Media found in database.<br>";
            } else {
                foreach ($media as $item) {
                     echo "📸 ID: {$item->id} | User: {$item->user_id} | Type: {$item->file_type} | Time: {$item->created_at}<br>";
                     echo "Path: " . asset('storage/' . $item->file_path) . "<br><hr>";
                }
            }
        } catch (\Exception $e) {
            echo "❌ Media Error: " . $e->getMessage() . "<br>";
        }
        
        echo "<h2>Storage Check</h2>";
        if (is_link(public_path('storage'))) {
            echo "✅ 'public/storage' symlink exists.<br>";
        } else {
            echo "❌ 'public/storage' symlink MISSING. Images will not load.<br>";
        }
        
        $uploadDir = storage_path('app/public/uploads');
        if (is_dir($uploadDir)) {
             echo "✅ Uploads directory exists: $uploadDir<br>";
             echo "Writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "<br>";
        } else {
             echo "⚠️ Uploads directory missing: $uploadDir (Will be created on first upload)<br>";
        }

        echo "<h2>Relationship Check</h2>";
        try {
            $user = \App\Models\User::first();
            if ($user) {
                echo "👤 User Found: {$user->id}<br>";
                $logs = $user->device_logs()->count(); // Test the relationship method
                echo "🔗 Relationship (device_logs) OK (Count: $logs)<br>";
            } else {
                echo "⚠️ No users found to test relationship.<br>";
            }
        } catch (\Throwable $e) {
            echo "❌ Relationship Error: " . $e->getMessage() . "<br>";
        }

    } catch (\Throwable $e) {
        echo "❌ Database Error: " . $e->getMessage();
    }
});
