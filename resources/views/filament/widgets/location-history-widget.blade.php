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
                <div class="col-span-2 border rounded relative">
                    <div id="map" style="height: 100%; width: 100%; z-index: 1;"></div>
                </div>
            </div>
        </div>
    </x-filament::section>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('livewire:initialized', () => {
            initMap();
        });
        
        // Re-init map when Livewire updates (e.g. date change)
        document.addEventListener('livewire:updated', () => {
             // Delay slightly to ensure DOM is ready
             setTimeout(initMap, 100);
        });

        let map = null;
        let markers = [];
        let polyline = null;

        function initMap() {
            const locations = @json($locations);
            
            // If map already exists, remove it to prevent duplicates on re-render
            if (map) {
                map.remove();
                map = null;
            }

            // Default view (e.g., center of world or user's last known location)
            let center = [0, 0];
            let zoom = 2;

            if (locations.length > 0) {
                center = [locations[0].latitude, locations[0].longitude];
                zoom = 13;
            }

            map = L.map('map').setView(center, zoom);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            if (locations.length > 0) {
                const latLngs = [];
                
                locations.forEach((loc, index) => {
                    const latLng = [loc.latitude, loc.longitude];
                    latLngs.push(latLng);
                    
                    const marker = L.marker(latLng).addTo(map)
                        .bindPopup(`
                            <b>${new Date(loc.created_at).toLocaleTimeString()}</b><br>
                            Battery: ${loc.battery_level}%<br>
                            ${loc.is_charging ? 'Charging ⚡' : ''}
                        `);
                    markers[index] = marker;
                });

                // Draw path
                polyline = L.polyline(latLngs, {color: 'blue'}).addTo(map);
                
                // Fit bounds to show all points
                map.fitBounds(polyline.getBounds());
            }
        }

        window.focusLocation = function(lat, lng, index) {
            if (map) {
                map.flyTo([lat, lng], 16);
                if (markers[index]) {
                    markers[index].openPopup();
                }
            }
        }
    </script>
</x-filament-widgets::widget>
