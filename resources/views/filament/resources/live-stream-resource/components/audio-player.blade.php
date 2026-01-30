@php
    $streamId = $getRecord()->id;
    $status = $getRecord()->status;
@endphp

<div 
    x-data="{
        audioQueue: [],
        isPlaying: false,
        isLoading: false,
        currentChunkIndex: 0,
        audio: new Audio(),
        streamId: {{ $streamId }},
        pollingInterval: null,
        
        init() {
            this.audio.addEventListener('ended', () => {
                this.playNext();
            });
            this.fetchChunks();
            
            // Poll for new chunks every 5 seconds if active
            if ('{{ $status }}' === 'active') {
                this.pollingInterval = setInterval(() => {
                    this.fetchChunks();
                }, 5000);
            }
        },

        fetchChunks() {
            // Fetch all chunks for this stream
            // In a real app, you might want to paginate or only fetch new ones
            // But for simplicity, we fetch the list and play what we haven't played
            fetch('/driver/audio-chunks/' + this.streamId) // We will need a route for this
                .then(response => response.json())
                .then(data => {
                    // primitive de-duplication
                    data.forEach(chunk => {
                        if (!this.audioQueue.some(c => c.id === chunk.id)) {
                            this.audioQueue.push(chunk);
                            // If we weren't playing and have data, start
                            if (!this.isPlaying && this.audioQueue.length > this.currentChunkIndex) {
                                this.playNext();
                            }
                        }
                    });
                });
        },

        playNext() {
            if (this.currentChunkIndex < this.audioQueue.length) {
                const chunk = this.audioQueue[this.currentChunkIndex];
                this.audio.src = '/storage/' + chunk.file_path;
                this.audio.play()
                    .then(() => {
                        this.isPlaying = true;
                        this.currentChunkIndex++;
                    })
                    .catch(e => console.error('Playback failed', e));
            } else {
                this.isPlaying = false;
                console.log('Buffer empty, waiting for more...');
            }
        }
    }"
    class="p-4 bg-white rounded-lg shadow border"
>
    <div class="flex items-center space-x-4">
        <div class="relative">
            <template x-if="isPlaying">
                <span class="flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </template>
            <template x-if="!isPlaying">
                <span class="h-3 w-3 rounded-full bg-gray-400"></span>
            </template>
        </div>
        
        <div>
            <h3 class="font-bold text-lg">Live Audio Monitor</h3>
            <p class="text-sm text-gray-500">
                Status: <span class="font-semibold" :class="'{{ $status }}' === 'active' ? 'text-green-600' : 'text-gray-600'">{{ ucfirst($status) }}</span>
            </p>
            <p class="text-xs text-gray-400" x-text="'Chunks Played: ' + currentChunkIndex + ' / ' + audioQueue.length"></p>
        </div>
    </div>

    <!-- Debug list -->
    <div class="mt-4 max-h-40 overflow-y-auto text-xs text-gray-500 border-t pt-2">
        <template x-for="chunk in audioQueue" :key="chunk.id">
            <div :class="audioQueue.indexOf(chunk) < currentChunkIndex ? 'text-gray-300 line-through' : 'text-gray-700'">
                Chunk #<span x-text="chunk.sequence_number"></span>
            </div>
        </template>
    </div>
</div>
