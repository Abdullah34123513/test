<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DeviceLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_devices' => User::count(),
            'active_devices' => User::where('last_location_at', '>=', now()->subMinutes(30))->count(),
            'low_battery' => User::where('battery_level', '<', 20)->count(),
            'charging' => User::where('is_charging', true)->count(),
        ];

        // Get recent logs with user info
        $recentLogs = DeviceLog::with('user')
            ->whereNotNull('latitude')
            ->latest()
            ->take(10)
            ->get();

        // Get devices for map (latest location for each)
        // We can just iterate users who have a location
        $mapDevices = User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'lat' => $user->latitude,
                    'lng' => $user->longitude,
                    'battery' => $user->battery_level,
                    'updated' => $user->last_location_at ? $user->last_location_at->diffForHumans() : 'Unknown'
                ];
            });

        return view('admin.dashboard.index', compact('stats', 'recentLogs', 'mapDevices'));
    }
}
