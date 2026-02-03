@extends('layouts.admin')

@section('header', 'Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Devices -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-400">Total Devices</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['total_devices'] }}</h3>
                </div>
                <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                    <i data-lucide="smartphone" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-gray-500">
                <span class="text-green-500 flex items-center font-medium">
                    <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                    All time
                </span>
                <span class="ml-2">monitored</span>
            </div>
        </div>

        <!-- Active Now -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-400">Active Now</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['active_devices'] }}</h3>
                </div>
                <div class="p-3 bg-green-50 rounded-lg text-green-600 group-hover:bg-green-600 group-hover:text-white transition">
                    <i data-lucide="wifi" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-gray-500">
                <span class="text-slate-400">Within last 30 mins</span>
            </div>
        </div>

        <!-- Low Battery -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-400">Low Battery</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['low_battery'] }}</h3>
                </div>
                <div class="p-3 bg-red-50 rounded-lg text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                    <i data-lucide="battery-warning" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-gray-500">
                <span class="text-red-500 font-medium">&lt; 20%</span>
                <span class="ml-2">critical level</span>
            </div>
        </div>

        <!-- Charging -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-400">Charging</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['charging'] }}</h3>
                </div>
                <div class="p-3 bg-yellow-50 rounded-lg text-yellow-600 group-hover:bg-yellow-600 group-hover:text-white transition">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-gray-500">
                <span class="text-slate-400">Plugged in</span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Live Map -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">Live Device Map</h2>
                <button class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View Full Screen</button>
            </div>
            <div class="flex-1 min-h-[400px] relative bg-gray-100">
                 <div id="dashboard-map" class="absolute inset-0"></div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800">Recent Activity</h2>
            </div>
            <div class="p-6 overflow-y-auto max-h-[400px] space-y-6">
                @forelse($recentLogs as $log)
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                             <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-slate-100 text-slate-500">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                             </span>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $log->user->name ?? 'Unknown Device' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Updated location at <span class="font-mono text-indigo-500">{{ number_format($log->latitude, 4) }}, {{ number_format($log->longitude, 4) }}</span>
                            </p>
                            <div class="mt-1 flex items-center text-xs text-gray-400">
                                <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                {{ $log->created_at->diffForHumans() }}
                                @if($log->battery_level)
                                    <span class="mx-2">•</span>
                                    <span class="{{ $log->battery_level < 20 ? 'text-red-500' : 'text-green-500' }}">
                                        {{ $log->battery_level }}% Battery
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-400">
                        No recent activity found.
                    </div>
                @endforelse
            </div>
            <div class="p-4 border-t border-gray-100 bg-gray-50 text-center rounded-b-xl">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 font-medium hover:underline">View All Devices</a>
            </div>
        </div>
    </div>

    <!-- Leaflet Config -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('dashboard-map').setView([0, 0], 2);
            
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var devices = @json($mapDevices);
            var bounds = [];

            devices.forEach(function(device) {
                var marker = L.marker([device.lat, device.lng]).addTo(map);
                marker.bindPopup(`
                    <div class="text-center">
                        <strong class="text-lg">${device.name}</strong><br>
                        <span class="text-gray-500 text-sm">Last seen: ${device.updated}</span><br>
                        <span class="text-xs font-bold ${device.battery < 20 ? 'text-red-500' : 'text-green-600'}">
                            Battery: ${device.battery}%
                        </span>
                    </div>
                `);
                bounds.push([device.lat, device.lng]);
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds, {padding: [50, 50]});
            } else {
                 map.setView([20, 0], 2); // Default world view
            }
        });
    </script>
@endsection
