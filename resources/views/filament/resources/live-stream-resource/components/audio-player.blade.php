@php
    $streamId = $getRecord()->id;
    $status = $getRecord()->status;
@endphp

<div 
    x-data="{
        audioQueue: [],
        playedChunkIds: new Set(),
        isPlaying: false,
        isLoading: true,
        bufferHealth: 0,
        currentSequence: -1,
        players: [new Audio(), new Audio()],
        activePlayerIndex: 0,
        streamId: {{ $streamId }},
        pollingInterval: null,
        
        init() {
            console.log('🚀 Live Player V2 Initialized');
            
            // Player Setup
            this.players.forEach((p, idx) => {
                p.addEventListener('ended', () => this.handleTrackEnd(idx));
                p.addEventListener('error', (e) => this.handleTrackError(idx, e));
                p.addEventListener('playing', () => { this.isLoading = false; });
            });
            
            // Initial Fetch
            this.fetchData();
            
            // Smart Polling (3s)
            if ('{{ $status }}' === 'active') {
                this.pollingInterval = setInterval(() => this.fetchData(), 3000);
            }
        },

        fetchData() {
            fetch('/driver/audio-chunks/' + this.streamId)
                .then(r => r.json())
                .then(data => {
                    // Filter: Only new chunks based on ID
                    const newChunks = data
                        .sort((a, b) => a.sequence_number - b.sequence_number)
                        .filter(c => !this.playedChunkIds.has(c.id) && !this.audioQueue.some(q => q.id === c.id));

                    if (newChunks.length > 0) {
                        console.log(`📥 Received ${newChunks.length} new chunks`);
                        this.audioQueue.push(...newChunks);
                        this.updateBufferHealth();
                        
                        // Auto-Start if stalled
                        if (!this.isPlaying && this.bufferHealth > 0) {
                            this.playNext();
                        }
                    }
                })
                .catch(err => console.error('Polling Error:', err));
        },

        updateBufferHealth() {
            this.bufferHealth = this.audioQueue.length;
        },

        playNext() {
            if (this.audioQueue.length === 0) {
                console.log('⚠️ Buffer Underrun: Waiting for data...');
                this.isPlaying = false;
                this.isLoading = true;
                return;
            }

            const chunk = this.audioQueue.shift(); // Dequeue
            this.playedChunkIds.add(chunk.id); // Mark as played
            
            // Sequence Check (Optional warning)
            if (this.currentSequence !== -1 && chunk.sequence_number !== this.currentSequence + 1) {
                console.warn(`⏩ Sequence Skip: ${this.currentSequence} -> ${chunk.sequence_number}`);
            }
            this.currentSequence = chunk.sequence_number;

            // Load and Play
            const player = this.players[this.activePlayerIndex];
            player.src = '/storage/' + chunk.file_path;
            
            console.log(`▶️ Playing Seq #${chunk.sequence_number} (Player ${this.activePlayerIndex})`);
            
            player.play()
                .then(() => {
                    this.isPlaying = true;
                    this.isLoading = false;
                    this.preloadNext(); // Prepare the OTHER player
                })
                .catch(e => {
                    console.error('Playback Start Failed:', e);
                    if (e.name === 'NotAllowedError') alert('Tap "Start Listening" to enable audio!');
                });
        },

        preloadNext() {
            if (this.audioQueue.length > 0) {
                const nextChunk = this.audioQueue[0];
                const nextPlayerIdx = (this.activePlayerIndex + 1) % 2;
                console.log(`💾 Preloading Seq #${nextChunk.sequence_number} on Player ${nextPlayerIdx}`);
                this.players[nextPlayerIdx].src = '/storage/' + nextChunk.file_path;
                this.players[nextPlayerIdx].load();
            }
        },

        handleTrackEnd(playerIdx) {
            console.log(`🏁 Track Finished (Player ${playerIdx})`);
            if (playerIdx === this.activePlayerIndex) {
                // Switch Players
                this.activePlayerIndex = (this.activePlayerIndex + 1) % 2;
                this.playNext(); // This will trigger the preloaded player
            }
        },

        handleTrackError(playerIdx, error) {
            console.error(`❌ Player ${playerIdx} Error:`, error);
            // Skip broken chunk and try next
            if (playerIdx === this.activePlayerIndex) {
                this.activePlayerIndex = (this.activePlayerIndex + 1) % 2;
                this.playNext();
            }
        },
        
        forceStart() {
            // Unlocks audio context
            this.players[0].play().catch(() => {}); 
            this.players[1].play().catch(() => {});
            this.playNext();
        }
    }"
    class="relative w-full"
>
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
