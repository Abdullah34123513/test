<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommandLogResource\Pages;
use App\Filament\Resources\CommandLogResource\RelationManagers;
use App\Models\CommandLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommandLogResource extends Resource
{
    protected static ?string $model = CommandLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('admin_id')
                    ->relationship('admin', 'name')
                    ->searchable()
                    ->label('Admin'),
                Forms\Components\TextInput::make('command')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(['pending' => 'Pending', 'delivered' => 'Delivered', 'executed' => 'Executed', 'failed' => 'Failed'])
                    ->required(),
                Forms\Components\Textarea::make('payload')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('response_message')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('delivered_at'),
                Forms\Components\DateTimePicker::make('executed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Initiated By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('command')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'screenshot' => 'info',
                        'capture_image' => 'info',
                        'start_stream' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'delivered' => 'info',
                        'executed' => 'success',
                        'failed' => 'danger',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('response_message')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('executed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommandLogs::route('/'),
            'create' => Pages\CreateCommandLog::route('/create'),
            'edit' => Pages\EditCommandLog::route('/{record}/edit'),
        ];
    }
}
