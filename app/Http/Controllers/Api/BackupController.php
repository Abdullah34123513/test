<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string', // e.g., 'contacts', 'call_log'
            'data' => 'required|json', // Expecting JSON string
        ]);

        $backupId = DB::table('backups')->insertGetId([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'data' => $request->data, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Backup stored successfully',
            'id' => $backupId,
        ], 201);
    }
}
