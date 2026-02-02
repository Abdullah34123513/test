<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function checkUpdate(Request $request)
    {
        $request->validate([
            'current_version_code' => 'required|integer',
        ]);

        $latestVersion = AppVersion::orderBy('version_code', 'desc')->first();

        if (!$latestVersion) {
            return response()->json([
                'update_available' => false,
                'message' => 'No versions found',
            ]);
        }

        $isUpdateAvailable = $latestVersion->version_code > $request->current_version_code;

        return response()->json([
            'update_available' => $isUpdateAvailable,
            'latest_version' => $latestVersion->version_code,
            'version_name' => $latestVersion->version_name,
            'apk_url' => $latestVersion->apk_url,
            'release_notes' => $latestVersion->release_notes,
            'force_update' => $latestVersion->is_force_update,
        ]);
    }
}
