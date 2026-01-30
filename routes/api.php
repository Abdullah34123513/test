<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/device-login', [\App\Http\Controllers\Api\AuthController::class, 'deviceLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/upload-media', [\App\Http\Controllers\Api\MediaController::class, 'upload']);
    Route::post('/backup-data', [\App\Http\Controllers\Api\BackupController::class, 'store']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
