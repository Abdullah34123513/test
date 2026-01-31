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
            // Can add other fields later like model, Android version etc if needed updates
        ]);

        $user = $request->user();
        
        if ($request->has('battery_level')) {
            $user->battery_level = $request->battery_level;
        }

        if ($request->has('is_charging')) {
            $user->is_charging = $request->boolean('is_charging');
        }

        $user->save();

        return response()->json(['message' => 'Device info updated']);
    }
}
