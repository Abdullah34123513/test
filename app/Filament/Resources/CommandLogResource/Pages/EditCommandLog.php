<?php

namespace App\Filament\Resources\CommandLogResource\Pages;

use App\Filament\Resources\CommandLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommandLog extends EditRecord
{
    protected static string $resource = CommandLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
