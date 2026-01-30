<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function deviceLogin(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'mac_address' => 'required|string',
            'model' => 'nullable|string',
            'location' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::firstOrCreate(
            ['device_id' => $request->device_id],
            [
                'mac_address' => $request->mac_address,
                'model' => $request->model,
                'location' => $request->location,
                'name' => $request->model ?? 'Unknown Device',
                'fcm_token' => $request->fcm_token,
                // Email and password are nullable now
            ]
        );

        // Update location/mac if changed? Optional.
        // For strict matching we used firstOrCreate. 
        // Let's update info if user exists.
        if (!$user->wasRecentlyCreated) {
            $user->update([
                'mac_address' => $request->mac_address,
                'model' => $request->model,
                'mac_address' => $request->mac_address,
                'model' => $request->model,
                'location' => $request->location,
                'fcm_token' => $request->fcm_token,
            ]);
        }

        $token = $user->createToken('device_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }
}
