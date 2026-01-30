@php
    $status = $getRecord()->status;
    $hasFile = \Illuminate\Support\Facades\Storage::disk('public')->exists("live_streams/{$getRecord()->id}/full_recording.m4a");
    $recordingUrl = \Illuminate\Support\Facades\Storage::url("live_streams/{$getRecord()->id}/full_recording.m4a");
@endphp

<div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
    @if($status === 'active')
        {{-- LIVE MODE: Use the Smart Gapless Player --}}
        <div class="mb-4 flex items-center gap-2">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
            <h3 class="text-lg font-bold text-gray-800">🔴 Live Audio Feed</h3>
        </div>
        
        @include('filament.resources.live-stream-resource.components.audio-player', ['streamId' => $getRecord()->id, 'status' => $status])

    @elseif($status === 'ended' && $hasFile)
        {{-- ARCHIVE MODE: Standard Player --}}
        <div class="mb-4 flex items-center gap-2">
            <h3 class="text-lg font-bold text-green-700">✅ Recording Available</h3>
        </div>
        
        <audio controls class="w-full h-12 rounded-lg" preload="metadata">
            <source src="{{ $recordingUrl }}" type="audio/mp4">
            Your browser does not support the audio element.
        </audio>
        
        <div class="mt-4 text-right">
            <a href="{{ $recordingUrl }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 active:bg-green-600 disabled:opacity-25 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Recording
            </a>
        </div>

    @else
        {{-- PROCESSING MODE: Waiting for Node.js --}}
        <div class="flex flex-col items-center justify-center p-8 text-center text-gray-500">
            <svg class="animate-spin h-10 w-10 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-lg font-medium">Processing Recording...</p>
            <p class="text-sm">Merging chunks on the server. Please refresh in a moment.</p>
        </div>
    @endif
</div>
