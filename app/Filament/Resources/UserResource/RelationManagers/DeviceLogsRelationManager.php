<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeviceLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'device_logs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('battery_level')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('created_at')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('battery_level')
                    ->label('Battery')
                    ->suffix('%')
                    ->color(fn (string $state): string => match (true) {
                        $state > 50 => 'success',
                        $state > 20 => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn ($record): string => $record->is_charging ? 'heroicon-s-bolt' : 'heroicon-o-battery-50'),
                Tables\Columns\IconColumn::make('is_charging')
                    ->boolean()
                    ->label('Charging'),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Location')
                    ->formatStateUsing(fn ($record) => $record->latitude && $record->longitude ?  "{$record->latitude}, {$record->longitude}" : 'N/A')
                    ->description(fn ($record) => $record->ip_address)
                    ->url(fn ($record) => $record->latitude && $record->longitude ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}" : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
