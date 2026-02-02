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
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('command')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('payload')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('response_message'),
                Forms\Components\DateTimePicker::make('delivered_at'),
                Forms\Components\DateTimePicker::make('executed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('command')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('response_message')
                    ->searchable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('executed_at')
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
            ])
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
