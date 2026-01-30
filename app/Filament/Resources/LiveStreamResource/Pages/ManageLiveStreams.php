<?php

namespace App\Filament\Resources\LiveStreamResource\Pages;

use App\Filament\Resources\LiveStreamResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLiveStreams extends ManageRecords
{
    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
