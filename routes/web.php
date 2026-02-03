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

        echo "<h2>Device Logs (All)</h2>";
        try {
            $logs = \App\Models\DeviceLog::latest()->take(10)->get();
            if ($logs->isEmpty()) {
                echo "⚠️ No Device Logs found.<br>";
            } else {
                echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>User</th><th>Data</th><th>Time</th></tr>";
                foreach ($logs as $log) {
                    echo "<tr><td>{$log->id}</td><td>{$log->user_id}</td><td><pre>" . htmlspecialchars(print_r($log->toArray(), true)) . "</pre></td><td>{$log->created_at}</td></tr>";
                }
                echo "</table>";
            }
        } catch (\Throwable $e) {
            echo "❌ Log Error: " . $e->getMessage() . "<br>";
        }

        echo "<h2>Laravel Log (Last 50 Lines)</h2>";
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $lines = file($logPath);
            $lastLines = array_slice($lines, -50);
            echo "<pre style='background:#eee; padding:10px; height:300px; overflow:scroll;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
        } else {
             echo "⚠️ Log file not found at $logPath<br>";
        }

        echo "<h2>User Check</h2>";
        $users = \App\Models\User::all();
        foreach ($users as $u) {
            echo "👤 ID: {$u->id} | Name: {$u->name} | Token: " . ($u->fcm_token ? substr($u->fcm_token, 0, 10) . '...' : '<span style="color:red">MISSING</span>') . "<br>";
        }

    } catch (\Throwable $e) {
        echo "❌ Database Error: " . $e->getMessage();
    }
});

Route::group(['prefix' => 'portal', 'middleware' => ['web', 'auth'], 'as' => 'admin.'], function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/command/{type}', [\App\Http\Controllers\Admin\UserController::class, 'command'])->name('users.command');
    Route::get('streams/{stream}', [\App\Http\Controllers\Admin\StreamController::class, 'show'])->name('streams.show');
});
