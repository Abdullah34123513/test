<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceInfoController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'battery_level' => 'nullable|integer|min:0|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_timestamp' => 'nullable|numeric',
        ]);

        $user = $request->user();
        
        // Update current state
        if ($request->has('battery_level')) $user->battery_level = $request->battery_level;
        if ($request->has('is_charging')) $user->is_charging = $request->boolean('is_charging');
        if ($request->has('latitude')) $user->latitude = $request->latitude;
        if ($request->has('longitude')) $user->longitude = $request->longitude;
        if ($request->has('location_timestamp')) $user->last_location_at = \Carbon\Carbon::createFromTimestamp($request->location_timestamp / 1000);

        $user->save();

        // Create History Log
        \App\Models\DeviceLog::create([
            'user_id' => $user->id,
            'battery_level' => $request->battery_level,
            'is_charging' => $request->boolean('is_charging'),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'ip_address' => $request->ip(),
            // 'network_type' => $request->network_type // If sent from app
        ]);

        return response()->json(['message' => 'Device info updated']);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'FCM token updated successfully']);
    }
}
