@extends('layouts.admin')

@section('header', 'Device Details')

@section('title', $user->name)

@section('content')
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center">
        <div class="flex items-center">
            <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-2xl mr-4">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="text-gray-500">{{ $user->email }}</p>
                <div class="mt-2 flex items-center space-x-2">
                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $user->last_location_at && $user->last_location_at->gt(now()->subMinutes(30)) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $user->last_location_at && $user->last_location_at->gt(now()->subMinutes(30)) ? 'Online' : 'Offline' }}
                    </span>
                    @if($user->battery_level)
                        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $user->battery_level < 20 ? 'bg-red-100 text-red-700' : 'bg-blue-50 text-blue-600' }}">
                            <i data-lucide="battery" class="inline w-3 h-3 mr-1"></i> {{ $user->battery_level }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
             <!-- Placeholder for Actions (we can implement API calls later) -->
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i> Request Location
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Main Map Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Current Location Map -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center">
                        <i data-lucide="map" class="w-5 h-5 mr-2 text-indigo-500"></i> Current Location
                    </h3>
                    <span class="text-xs text-gray-500">
                        Updated {{ $user->last_location_at ? $user->last_location_at->diffForHumans() : 'Never' }}
                    </span>
                </div>
                <div class="h-[500px] relative bg-gray-100">
                    @if($user->latitude && $user->longitude)
                        <iframe 
                            id="main-map"
                            width="100%" 
                            height="100%" 
                            style="border:0" 
                            loading="lazy" 
                            allowfullscreen
                            src="https://maps.google.com/maps?q={{ $user->latitude }},{{ $user->longitude }}&z=15&output=embed">
                        </iframe>
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-gray-400">
                            Location data not available
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-8">
            <!-- Device Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Device Information</h3>
                <div class="space-y-4">
                    <div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-gray-500">Model</span>
                        <span class="font-medium">{{ $user->model ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-gray-500">Device ID</span>
                        <span class="font-mono text-xs text-gray-600 truncate max-w-[150px]" title="{{ $user->device_id }}">{{ $user->device_id ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-gray-500">Charging</span>
                        <span class="font-medium {{ $user->is_charging ? 'text-green-600' : 'text-gray-600' }}">
                            {{ $user->is_charging ? 'Yes ⚡' : 'No' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-gray-500">Update Interval</span>
                        <span class="font-medium">{{ $user->location_update_interval ?? 30 }}m</span>
                    </div>
                </div>
            </div>

            <!-- Location History Timeline -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col max-h-[600px]">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800">Location History (Recent)</h3>
                </div>
                <div class="overflow-y-auto p-2 space-y-2 flex-1">
                    @forelse($user->device_logs as $log)
                        @if($log->latitude && $log->longitude)
                            <div onclick="updateMap({{ $log->latitude }}, {{ $log->longitude }})" 
                                 class="p-3 rounded-lg border border-gray-100 hover:bg-indigo-50 hover:border-indigo-100 cursor-pointer transition group">
                                <div class="flex justify-between items-start">
                                    <span class="font-mono text-xs text-indigo-600 font-semibold group-hover:text-indigo-700">
                                        {{ $log->created_at->format('H:i:s') }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $log->created_at->format('M d') }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <div class="text-xs text-gray-600">
                                        Battery: {{ $log->battery_level }}%
                                    </div>
                                    <div class="text-xs font-medium text-gray-400 group-hover:text-indigo-500">
                                        Click to view
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="p-4 text-center text-gray-400 text-sm">No history available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateMap(lat, lng) {
            const iframe = document.getElementById('main-map');
            if (iframe) {
                iframe.src = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;
            }
        }
    </script>
@endsection
