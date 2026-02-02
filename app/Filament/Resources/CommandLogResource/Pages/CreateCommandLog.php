<?php

namespace App\Filament\Resources\CommandLogResource\Pages;

use App\Filament\Resources\CommandLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCommandLog extends CreateRecord
{
    protected static string $resource = CommandLogResource::class;
}
