<?php

namespace App\Filament\Resources\UserResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use App\Models\DeviceLog;
use Carbon\Carbon;

class LocationHistoryWidget extends Widget
{
    protected static string $view = 'filament.widgets.location-history-widget';

    public ?Model $record = null;
    public $date;

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function filter()
    {
        // This method is called when the form is submitted (date changed)
        // Livewire automatically updates $this->date
    }

    protected function getViewData(): array
    {
        $locations = [];

        if ($this->record) {
            $locations = DeviceLog::where('user_id', $this->record->id)
                ->whereDate('created_at', $this->date)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return [
            'locations' => $locations,
        ];
    }
}
