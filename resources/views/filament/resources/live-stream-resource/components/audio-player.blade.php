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
                const url = '/storage/' + chunk.file_path;
                console.log('Playing:', url);
                
                this.audio.src = url;
                this.audio.play()
                    .then(() => {
                        this.isPlaying = true;
                        this.currentChunkIndex++;
                    })
                    .catch(e => {
                        console.error('Playback failed', e);
                        this.isPlaying = false;
                        // Determine if it's an interaction error
                        if (e.name === 'NotAllowedError') {
                             alert('Please click "Start Listening" to enable audio playback.');
                        }
                    });
            } else {
                this.isPlaying = false;
                console.log('Buffer empty, waiting for more...');
            }
        },
        
        startListening() {
             // User interaction to unlock audio
             this.audio.play().catch(() => {}); 
             this.playNext();
        }
    }"
    class="p-4 bg-white rounded-lg shadow border"
>
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <button 
                @click="startListening()"
                x-show="!isPlaying && audioQueue.length > 0"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-bold"
            >
                Start Listening
            </button>
            
            <div class="relative" x-show="isPlaying">
                <span class="flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            
            <div>
                <h3 class="font-bold text-lg">Live Audio Monitor</h3>
                <p class="text-sm text-gray-500">
                    Status: <span class="font-semibold" :class="'{{ $status }}' === 'active' ? 'text-green-600' : 'text-gray-600'">{{ ucfirst($status) }}</span>
                </p>
                <p class="text-xs text-gray-400" x-text="'Chunks: ' + currentChunkIndex + ' / ' + audioQueue.length"></p>
            </div>
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
