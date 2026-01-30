@php
    $streamId = $getRecord()->id;
    $status = $getRecord()->status;
@endphp

<div 
    x-data="{
        audioQueue: [],
        isPlaying: false,
        currentChunkIndex: 0,
        players: [new Audio(), new Audio()],
        activePlayerIndex: 0,
        streamId: {{ $streamId }},
        pollingInterval: null,
        
        init() {
            // Setup dual players for gapless playback
            this.players.forEach((p, idx) => {
                p.addEventListener('ended', () => {
                    this.playNext(idx); // Pass who finished
                });
                p.addEventListener('error', (e) => {
                    console.error('Player error:', e);
                    this.playNext(idx); // Skip on error
                });
            });
            
            this.fetchChunks();
            
            if ('{{ $status }}' === 'active') {
                this.pollingInterval = setInterval(() => {
                    this.fetchChunks();
                }, 5000);
            }
        },

        fetchChunks() {
            fetch('/driver/audio-chunks/' + this.streamId)
                .then(response => response.json())
                .then(data => {
                    let newChunks = false;
                    data.forEach(chunk => {
                        if (!this.audioQueue.some(c => c.id === chunk.id)) {
                            this.audioQueue.push(chunk);
                            newChunks = true;
                        }
                    });
                    
                    // If we found new chunks and aren't playing, start or preload
                    if (newChunks && !this.isPlaying) {
                        if (this.currentChunkIndex === 0 && this.audioQueue.length > 0) {
                            // Auto-start or wait for user? 
                            // We wait for user interaction usually, but preload first
                             this.preload(this.activePlayerIndex, this.audioQueue[this.currentChunkIndex]);
                        } else if (this.currentChunkIndex < this.audioQueue.length) {
                             // Resuming
                             this.playChunk(this.activePlayerIndex, this.audioQueue[this.currentChunkIndex]);
                        }
                    }
                });
        },

        preload(playerIdx, chunk) {
            if (!chunk) return;
            const url = '/storage/' + chunk.file_path;
            this.players[playerIdx].src = url;
            this.players[playerIdx].load();
        },

        playChunk(playerIdx, chunk) {
             if (!chunk) return;
             const url = '/storage/' + chunk.file_path;
             console.log('Playing Chunk ' + chunk.sequence_number + ' on Player ' + playerIdx);
             
             // Ensure src is set (might be already preloaded)
             if (!this.players[playerIdx].src.includes(chunk.file_path)) {
                 this.players[playerIdx].src = url;
             }
             
             this.players[playerIdx].play()
                .then(() => {
                    this.isPlaying = true;
                    // Preload NEXT chunk on the OTHER player
                    const nextChunk = this.audioQueue[this.currentChunkIndex + 1];
                    const otherPlayerIdx = (playerIdx + 1) % 2;
                    if (nextChunk) {
                        this.preload(otherPlayerIdx, nextChunk);
                    }
                })
                .catch(e => {
                    console.error('Play failed', e);
                    if (e.name === 'NotAllowedError') {
                         this.isPlaying = false; // Wait for user click
                         alert('Please click \'Start Listening\' to enable audio playback.');
                    }
                });
        },

        playNext(finishedPlayerIdx) {
            // Determine logical next index
            this.currentChunkIndex++;
            
            if (this.currentChunkIndex < this.audioQueue.length) {
                // Switch to the other player (which should be preloaded)
                const nextPlayerIdx = (finishedPlayerIdx + 1) % 2;
                this.activePlayerIndex = nextPlayerIdx;
                
                // Play immediately
                this.playChunk(nextPlayerIdx, this.audioQueue[this.currentChunkIndex]);
            } else {
                this.isPlaying = false;
                console.log('Stream caught up / ended');
            }
        },
        
        startListening() {
             // User unlock
             const chunk = this.audioQueue[this.currentChunkIndex];
             if (chunk) {
                 this.playChunk(this.activePlayerIndex, chunk);
             }
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
