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
        try {
            if (!class_exists(\App\Models\DeviceLog::class)) {
                throw new \Exception("Class \App\Models\DeviceLog not found!");
            }
            $count = \App\Models\DeviceLog::count();
            echo "📊 DeviceLog Count: $count<br>";
        } catch (\Throwable $e) {
            echo "❌ DeviceLog Model Error: " . $e->getMessage() . "<br>";
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
