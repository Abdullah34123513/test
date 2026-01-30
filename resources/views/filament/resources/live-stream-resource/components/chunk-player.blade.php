<div class="flex justify-center p-4">
    <audio controls autoplay class="w-full">
        <source src="{{ url('storage/' . $record->file_path) }}" type="audio/mp4">
        Your browser does not support the audio element.
    </audio>
</div>
