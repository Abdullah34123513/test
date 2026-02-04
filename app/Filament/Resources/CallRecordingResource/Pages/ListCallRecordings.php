<?php

namespace App\Filament\Resources\CallRecordingResource\Pages;

use App\Filament\Resources\CallRecordingResource;
use Filament\Resources\Pages\ListRecords;

class ListCallRecordings extends ListRecords
{
    protected static string $resource = CallRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
