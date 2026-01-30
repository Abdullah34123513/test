<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiveStreamResource\Pages;
use App\Filament\Resources\LiveStreamResource\RelationManagers;
use App\Models\LiveStream;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Infolists;
use Filament\Infolists\Infolist;

class LiveStreamResource extends Resource
{
    protected static ?string $model = LiveStream::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Live Player')
                    ->schema([
                        Infolists\Components\ViewEntry::make('player')
                            ->view('filament.resources.live-stream-resource.components.audio-player')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'ended' => 'gray',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('started_at')->dateTime(),
                        Infolists\Components\TextEntry::make('ended_at')->dateTime(),
                    ])->columns(2),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'ended' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'ended' => 'Ended',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\ChunksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLiveStreams::route('/'),
        ];
    }
}
