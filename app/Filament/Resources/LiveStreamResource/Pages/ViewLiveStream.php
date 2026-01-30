<?php

namespace App\Filament\Resources\LiveStreamResource\Pages;

use App\Filament\Resources\LiveStreamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLiveStream extends ViewRecord
{
    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_full')
                ->label('Download Full Recording')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (\App\Models\LiveStream $record) => \Illuminate\Support\Facades\Storage::url("live_streams/{$record->id}/full_recording.m4a"))
                ->openUrlInNewTab()
                ->visible(fn (\App\Models\LiveStream $record) => \Illuminate\Support\Facades\Storage::disk('public')->exists("live_streams/{$record->id}/full_recording.m4a")),
        ];
    }
}
