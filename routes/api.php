<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/device-login', [\App\Http\Controllers\Api\AuthController::class, 'deviceLogin']);
Route::post('/forgot-password', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [\App\Http\Controllers\Api\ResetPasswordController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/upload-media', [\App\Http\Controllers\Api\MediaController::class, 'upload']);
    Route::post('/backup-data', [\App\Http\Controllers\Api\BackupController::class, 'store']);
    
    // Live Streaming
    Route::post('/stream/start', [\App\Http\Controllers\Api\LiveStreamController::class, 'start']);
    Route::post('/stream/chunk', [\App\Http\Controllers\Api\LiveStreamController::class, 'uploadChunk']);
    Route::post('/stream/end', [\App\Http\Controllers\Api\LiveStreamController::class, 'end']);
    
    // Device Info
    Route::post('/update-device-info', [\App\Http\Controllers\Api\DeviceInfoController::class, 'update']);
    Route::post('/update-fcm-token', [\App\Http\Controllers\Api\DeviceInfoController::class, 'updateFcmToken']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // App Update
    Route::get('/app/check-update', [\App\Http\Controllers\Api\AppVersionController::class, 'checkUpdate']);

    // Command Logging
    Route::post('/command/status', [\App\Http\Controllers\Api\CommandLogController::class, 'updateStatus']);

    // Chat
    Route::get('/users', [\App\Http\Controllers\Api\ChatController::class, 'getUsers']);
    Route::get('/messages/{userId}', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/messages', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);
});
