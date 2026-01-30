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
            Actions\Action::make('merge')
                ->label('Process Recording')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->requiresConfirmation()
                ->action(function (\App\Models\LiveStream $record) {
                    $script = base_path('merge_processor.cjs');
                    $command = "node " . escapeshellarg($script) . " " . escapeshellarg($record->id) . " 2>&1";
                    
                    exec($command, $output, $returnVar);
                    
                    if ($returnVar === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Audio Merged Successfully')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Merge Failed')
                            ->body(implode("\n", $output))
                            ->danger()
                            ->send();
                    }
                }),
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
