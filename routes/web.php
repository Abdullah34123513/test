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
        
    } catch (\Exception $e) {
        echo "❌ Database Error: " . $e->getMessage();
    }
});
