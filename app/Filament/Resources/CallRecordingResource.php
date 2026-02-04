<?php

namespace App\Filament\Resources;

use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallRecordingResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Call Recordings';

    protected static ?string $pluralModelLabel = 'Call Recordings';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', 'call_recording');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('file_path')
                    ->required(),
                Forms\Components\TextInput::make('file_type'),
                Forms\Components\TextInput::make('file_size')
                    ->numeric(),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('player')
                    ->label('Playback')
                    ->view('filament.resources.call-recording-resource.components.audio-player'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Media $record) => \Illuminate\Support\Facades\Storage::url($record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => CallRecordingResource\Pages\ListCallRecordings::route('/'),
        ];
    }
}
