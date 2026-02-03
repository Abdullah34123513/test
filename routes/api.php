<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/device-login', [\App\Http\Controllers\Api\AuthController::class, 'deviceLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/upload-media', [\App\Http\Controllers\Api\MediaController::class, 'upload']);
    Route::post('/backup-data', [\App\Http\Controllers\Api\BackupController::class, 'store']);
    
    // Live Streaming
    Route::post('/stream/start', [\App\Http\Controllers\Api\LiveStreamController::class, 'start']);
    Route::post('/stream/chunk', [\App\Http\Controllers\Api\LiveStreamController::class, 'uploadChunk']);
    Route::post('/stream/end', [\App\Http\Controllers\Api\LiveStreamController::class, 'end']);
    
    // Device Info
    Route::post('/update-device-info', [\App\Http\Controllers\Api\DeviceInfoController::class, 'update']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // App Update
    Route::get('/app/check-update', [\App\Http\Controllers\Api\AppVersionController::class, 'checkUpdate']);

    // Command Logging
    Route::post('/command/status', [\App\Http\Controllers\Api\CommandLogController::class, 'updateStatus']);
});
