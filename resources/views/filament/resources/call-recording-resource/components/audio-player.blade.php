<div class="flex items-center justify-center">
    <audio controls class="w-full max-w-xs">
        <source src="{{ \Illuminate\Support\Facades\Storage::url($getState()) }}" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
</div>
