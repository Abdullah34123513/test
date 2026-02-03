<div class="rounded-lg border overflow-hidden" style="min-height: 300px;">
    @if($getRecord()->latitude && $getRecord()->longitude)
        <iframe 
            width="100%" 
            height="300" 
            style="border:0" 
            loading="lazy" 
            allowfullscreen
            src="https://maps.google.com/maps?q={{ $getRecord()->latitude }},{{ $getRecord()->longitude }}&z=15&output=embed">
        </iframe>
    @else
        <div class="flex items-center justify-center h-[300px] bg-gray-100 text-gray-500">
            No location data available
        </div>
    @endif
</div>
<div class="text-xs text-gray-500 mt-1 text-right">
    Last Updated: {{ $getRecord()->last_location_at?->diffForHumans() ?? 'Never' }}
</div>
