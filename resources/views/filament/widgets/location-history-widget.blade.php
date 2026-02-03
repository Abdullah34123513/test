<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-bold">Location History</h2>
                <form wire:submit.prevent="filter" class="flex gap-2">
                    <input type="date" wire:model.live="date" class="border rounded px-2 py-1 text-black">
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 h-[500px]">
                <!-- Timeline List -->
                <div class="col-span-1 overflow-y-auto border rounded p-2 bg-gray-50 dark:bg-gray-800">
                    @if($locations->isEmpty())
                        <p class="text-center text-gray-500 py-4">No location data for this date.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($locations as $index => $loc)
                                <div class="p-2 border rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                     onclick="focusLocation({{ $loc->latitude }}, {{ $loc->longitude }}, {{ $index }})">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-sm">{{ $loc->created_at->format('H:i:s') }}</span>
                                        <span class="text-xs text-gray-500">{{ $loc->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-xs mt-1">
                                        Battery: {{ $loc->battery_level }}% 
                                        @if($loc->is_charging) <span class="text-green-600">⚡</span> @endif
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1 truncate">
                                        {{ number_format($loc->latitude, 5) }}, {{ number_format($loc->longitude, 5) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Map Container -->
                <div class="col-span-2 border rounded relative bg-gray-100">
                    <iframe 
                        id="google-map"
                        width="100%" 
                        height="100%" 
                        style="border:0; min-height: 500px;" 
                        loading="lazy" 
                        allowfullscreen
                        src="about:blank">
                    </iframe>
                    
                    <div id="map-placeholder" class="absolute inset-0 flex items-center justify-center text-gray-500 bg-gray-50">
                        <p>Select a location from the left to view on Google Maps</p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    <script>
        document.addEventListener('livewire:initialized', () => {
            initMap();
        });
        
        document.addEventListener('livewire:updated', () => {
             initMap();
        });

        function initMap() {
            const locations = @json($locations);
            if (locations.length > 0) {
                // Auto-select the last location (most recent)
                const last = locations[locations.length - 1]; // or index 0 if ordered desc
                focusLocation(last.latitude, last.longitude);
            }
        }

        window.focusLocation = function(lat, lng, index) {
            const iframe = document.getElementById('google-map');
            const placeholder = document.getElementById('map-placeholder');
            
            // Hide placeholder
            if (placeholder) placeholder.style.display = 'none';
            
            // Update Iframe Source
            // standard embed url for a marker
            iframe.src = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;
        }
    </script>
</x-filament-widgets::widget>
